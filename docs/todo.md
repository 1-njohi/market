
# TODO

## Withdrawals — M-Pesa callback path broken

`MpesaWithdrawalService::handleResult` rejects every Safaricom result callback
because the guard checks `isPending()` while `initiate()` sets the withdrawal
to `PROCESSING` before the callback arrives. Effect: no withdrawal ever settles;
users see "processing" forever; the wallet debit is never confirmed or refunded.

**Fix:** guard on terminal states instead of initial state.

    if ($withdrawal->isCompleted() || $withdrawal->isFailed()) { ... return; }

**Also unresolved in the same file:**

- `handleResult` failure path refunds the wallet but writes no notification
- `initiate`'s synchronous `catch (Throwable)` block refunds and marks failed,
  same missing notification
- No test coverage of either path

**When picked up:** write `tests/Feature/Withdrawal/MpesaCallbackTest.php`
first. Two tests: success (`ResultCode = 0` → `COMPLETED`), failure
(`ResultCode != 0` → wallet refunded, `FAILED`). Both should fail today.

Blocks: `WithdrawalCompletedNotification` / `WithdrawalFailedNotification`.
