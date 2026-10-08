import { computed, ref } from 'vue';

const DISMISS_KEY = 'dash_onboarding_dismissed';

export function useOnboarding(data, { role = 'buyer', thresholds = {} } = {}) {
    const dismissed = ref(localStorage.getItem(DISMISS_KEY) === '1');

    // Prefer backend signal if available (TDD: will exist later)
    const backendFlag = data?.onboarding?.is_first_session;

    const counts = computed(() => {
        if (role === 'buyer') {
            return {
                purchases: data?.performance?.total_purchases ?? 0,
                watches: data?.watch_record?.settled_count ?? 0,
                following: data?.following_stats?.total ?? 0,
                contests: data?.contests?.length ?? 0,
            };
        }
        return {
            betslips: data?.performance?.total_betslips ?? 0,
            sales: data?.performance?.total_sold ?? 0,
            contests: data?.contests?.length ?? 0,
            followers: data?.follower_stats?.total ?? 0,
        };
    });

    const totalActivity = computed(() =>
        Object.values(counts.value).reduce((a, b) => a + b, 0),
    );

    const state = computed(() => {
        if (backendFlag === true) return 'fresh';
        if (backendFlag === false && totalActivity.value > 0) return 'active';
        if (totalActivity.value === 0) return 'fresh';
        if (totalActivity.value < 5) return 'warming';
        return 'active';
    });

    const shouldShowBanner = computed(
        () => state.value === 'fresh' && !dismissed.value,
    );

    const dismiss = () => {
        dismissed.value = true;
        localStorage.setItem(DISMISS_KEY, '1');
    };

    return { state, counts, shouldShowBanner, dismiss };
}