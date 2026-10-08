import { computed, ref, watch } from 'vue';

const PERIODS = ['24h', '7d', '30d', '90d', 'ytd', 'all'];

const LABELS = {
    '24h': 'Last 24h',
    '7d': 'Last 7 days',
    '30d': 'Last 30 days',
    '90d': 'Last 90 days',
    ytd: 'Year to date',
    all: 'All time',
};

const SHORT_LABELS = {
    '24h': '24H',
    '7d': '7D',
    '30d': '30D',
    '90d': '90D',
    ytd: 'YTD',
    all: 'ALL',
};

const STORAGE_KEY = 'dash_period_v1';

/**
 * Returns a reactive period ref + helper to turn it into a Date cutoff.
 *
 * Pass a `scope` (e.g. 'buyer' or 'seller') to keep the two dashboards'
 * preferences independent.
 */
export function usePeriod(scope = 'default', initial = '30d') {
    const key = `${STORAGE_KEY}_${scope}`;

    const stored =
        typeof window !== 'undefined' ? localStorage.getItem(key) : null;
    const start = PERIODS.includes(stored) ? stored : initial;

    const period = ref(start);

    watch(period, (val) => {
        if (typeof window !== 'undefined') {
            localStorage.setItem(key, val);
        }
    });

    const label = computed(() => LABELS[period.value] ?? period.value);
    const shortLabel = computed(
        () => SHORT_LABELS[period.value] ?? period.value.toUpperCase(),
    );

    /**
     * Returns a Date cutoff for the current period, or null for 'all'.
     */
    const since = computed(() => {
        const now = new Date();
        switch (period.value) {
            case '24h':
                return new Date(now.getTime() - 24 * 60 * 60 * 1000);
            case '7d':
                return new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
            case '30d':
                return new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
            case '90d':
                return new Date(now.getTime() - 90 * 24 * 60 * 60 * 1000);
            case 'ytd':
                return new Date(now.getFullYear(), 0, 1);
            case 'all':
            default:
                return null;
        }
    });

    /**
     * Filter any array of objects by a date field.
     * Rows with a missing/null date are dropped when a cutoff is active.
     */
    const filterByDate = (items, dateField = 'created_at') => {
        if (!Array.isArray(items)) return [];
        if (!since.value) return items;
        const cutoff = since.value.getTime();
        return items.filter((item) => {
            const raw = item?.[dateField];
            if (!raw) return false;
            const t = new Date(raw).getTime();
            return !Number.isNaN(t) && t >= cutoff;
        });
    };

    return {
        period,
        periods: PERIODS,
        label,
        shortLabel,
        since,
        filterByDate,
        setPeriod: (p) => {
            if (PERIODS.includes(p)) period.value = p;
        },
    };
}