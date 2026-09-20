
# Wallet & Ledger

How money moves in the platform. Read this before touching
`WalletService`, the dashboards, or anything that writes to `wallets`
or `transactions`.

## The four primitives

All wallet mutation goes through `App\Services\WalletService`. No
other class writes to `wallets.balance` or `wallets.escrow_balance`.

| Method                                                             | Effect                                                                   | Ledger rows                                                  |
| ------------------------------------------------------------------ | ------------------------------------------------------------------------ | ------------------------------------------------------------ |
| `credit($user, $amount)`                                         | `available += amount`                                                  | 1 (available, +amount)                                       |
| `debit($user, $amount)`                                          | `available -= amount`                                                  | 1 (available, -amount)                                       |
| `hold($user, $amount)`                                           | `available -= amount`, `escrow += amount`                            | 2 (available -, escrow +)                                    |
| `refundEscrow($user, $amount)`                                   | `escrow -= amount`, `available += amount`                            | 2 (escrow -, available +)                                    |
| `settleEscrowToSeller($buyer, $seller, $platform, $gross, $fee)` | Buyer escrow`-= gross`; seller `+= gross - fee`; platform `+= fee` | 4 (buyer escrow -, seller gross +, seller fee -, platform +) |

Every primitive:

- Opens its own `DB::transaction`.
- Locks every wallet it touches via `SELECT ... FOR UPDATE`, in ascending
  `user_id` order (prevents deadlocks when two operations touch the same
  pair of wallets).
- Writes one or more `transactions` rows.
- Rejects non-positive amounts and amounts with more than 2 decimals.
- Throws `InsufficientBalanceException` on overdraft.

## The escrow model

Money is held in the **buyer's** wallet, not the seller's, between
purchase and settlement.

**Purchase** — buyer's `available` drops, buyer's `escrow` rises.
Seller's wallet is untouched.

**Settlement — win** — buyer's escrow drains. Seller receives gross,
then pays the platform fee from gross. Platform receives fee. Net
effect: buyer paid `price` and got nothing back; seller received
`price - fee`; platform received `fee`.

**Settlement — loss** — buyer's escrow drains back to buyer's
`available`. Seller and platform untouched.

**Settlement — all void** — same as loss, with the pivot status set to
`voided`.

This is why there is no seller-side "pending balance." A seller's
lifetime earnings live in their `available` balance; projected future
earnings (from unsettled purchases) live in the dashboard layer,
computed from `betslip_user_purchases` where `status = 'pending'`.
Never store a projection in a wallet column.

## `balance_type` — the ledger's two sides

Every `transactions` row carries a `balance_type`:

- **`available`** — user-facing money. Every row with this value shows
  up in the user's transactions table.
- **`escrow`** — internal bookkeeping. The mirror leg of `hold()` and
  `refundEscrow()`, and the buyer's debit leg of `settleEscrowToSeller()`.
  Filtered out of every user-facing list.

**Rule:** any query that shows money to a user MUST filter
`->where('balance_type', 'available')`.

## `transactionable` — money rows know their cause

`transactions` uses a polymorphic `transactionable` link. In practice
it points at `BetslipUserPurchase`.

- Purchase's available leg — tagged with the pivot.
- Refund and settlement money rows — all tagged with the pivot.
- Purchase's escrow leg — **untagged** (internal).

This lets any single money row answer "which betslip did this come
from?" — and lets the buyer dashboard derive an outcome badge for each
purchase or refund row.

Morph queries scoped to a user must filter by `user_id` in addition to
`transactionable_id`. A single pivot can be tagged onto rows belonging
to the buyer, the seller, and the platform. `getSettlements()` on the
seller dashboard filters `->where('user_id', $purchase->seller_id)` for
exactly this reason.

## Status vocabularies

Three separate status fields exist. Don't mix them.

**`betslips.status`** — lifecycle of a tip.
`pending | underway | settled | voided`

**`betslip_user_purchases.status`** — lifecycle of one buyer's purchase.
`pending | won | refunded | voided`

**`transactions.status`** — always `completed` for a ledger row that
actually moved money. `pending`/`failed` reserved for future async flows.

The `won | refunded | voided` set is the terminal state of a purchase.
Win rate counts `won` against `won + refunded`; voided is a push and
belongs in neither numerator nor denominator.

## Adding a new primitive

If you need a new money movement:

1. Add a public method to `WalletService`.
2. Open a `DB::transaction`.
3. Call `lockWallet()` (single) or `lockWallets()` (multiple, ordered).
4. Read balances, validate, mutate, `->save()`.
5. Write ledger rows via `createTransaction()`.
6. Add a test in `tests/Feature/Wallet/`.

Never write to `wallets.*` from anywhere else.

## Testing

The relevant test files:

- `tests/Feature/Wallet/PurchaseEscrowTest.php` — hold/refund primitives.
- `tests/Feature/Wallet/SettlementTest.php` — settle / refund / fee.
- `tests/Feature/Settlement/BetslipSettlementTest.php` — end-to-end settlement flow.
- `tests/Feature/Dashboard/WalletSummaryTest.php` — dashboard projections.
- `tests/Feature/Health/WalletIntegrityTest.php` — invariants.

## Invariants checked by `SystemHealthService`

- `SUM(transactions.amount) == wallets.balance + wallets.escrow_balance`
  per user.
- `SUM(wallets.escrow_balance) == SUM(pending purchase_price)` across
  the platform.

If either fails, something wrote to a wallet outside `WalletService`, or
a settlement transitioned a pivot without moving the matching escrow.

## Activity feed events

`getRecentActivity()` on both dashboard services returns a chronologically
sorted list of events. Every event has the same shape, regardless of
which service produced it:

```php
[
    'kind'       => string,   // purchase | sale | settlement_won  | settlement_lost | settlement_voided
    'badge'      => string,   // single character shown in the tile
    'tone'       => string,   // positive | negative | neutral | pending
    'message'    => string,   // human-readable description
    'amount'     => ?float,   // money involved, or null for non-cash events
    'created_at' => string,   // ISO 8601
    'time_ago'   => string,   // "3 minutes ago"
]
```

**Tone → color** (both dashboards, same mapping):

| tone         | color   | used for                           |
| ------------ | ------- | ---------------------------------- |
| `positive` | emerald | money in, or outcome in your favor |
| `negative` | rose    | money out, commitment made         |
| `neutral`  | slate   | outcome with no net movement       |
| `pending`  | amber   | reserved for async flows           |

**Why tone, not kind.** The Vue template colors by `tone` and renders
`badge`, so adding a new `kind` only requires a backend change. The
template never branches on `kind`.

**Tone is a business judgment, not a sign.** A `settlement_lost` event is
`neutral`, not `negative` — the buyer got a refund, so nothing was lost.
A `sale` event on the seller's feed is `neutral`, not `positive` — the
money is still in the buyer's escrow until the betslip settles.
