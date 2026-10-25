import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import SubLeagueModal from '../SubLeagueModal.vue';

vi.mock('axios', () => ({
    default: { post: vi.fn(() => Promise.resolve({ data: {} })) },
}));
vi.mock('@inertiajs/vue3', () => ({
    router: { reload: vi.fn() },
}));

const axios = (await import('axios')).default as any;

const leagueOptions = [
    { code: 'EPL', label: 'Premier League' },
    { code: 'UCL', label: 'UEFA Champions League' },
];

describe('SubLeagueModal — create mode', () => {
    beforeEach(() => {
        axios.post.mockClear();
    });

    const mountCreate = () =>
        mount(SubLeagueModal, {
            props: {
                open: true,
                mode: 'create',
                seasons: ['2026-27'],
                activeSeason: '2026-27',
                leagueOptions,
            },
        });

    it('renders one pill per league option', () => {
        const wrapper = mountCreate();
        const pills = wrapper.findAll('[data-testid="league-pill"]');
        expect(pills).toHaveLength(2);
        expect(pills[0].text()).toContain('Premier League');
        expect(pills[1].text()).toContain('UEFA Champions League');
    });

    it('disables submit until name, season, and at least one league are set', async () => {
        const wrapper = mountCreate();
        const submit = wrapper.find('[data-testid="submit"]');
        expect(submit.attributes('disabled')).toBeDefined();

        await wrapper.find('#name').setValue('Sunday Crew');
        await wrapper.find('[data-testid="league-pill"]').trigger('click');

        expect(submit.attributes('disabled')).toBeUndefined();
    });

    it('sends selected league codes in the POST body', async () => {
        const wrapper = mountCreate();
        await wrapper.find('#name').setValue('Sunday Crew');
        await wrapper.findAll('[data-testid="league-pill"]')[0].trigger('click');   // EPL
        await wrapper.findAll('[data-testid="league-pill"]')[1].trigger('click');   // UCL

        await wrapper.find('[data-testid="submit"]').trigger('click');

        expect(axios.post).toHaveBeenCalledWith('/sub-leagues', {
            name: 'Sunday Crew',
            season: '2026-27',
            league_codes: ['EPL', 'UCL'],
        });
    });

    it('toggles a league off when clicked twice', async () => {
        const wrapper = mountCreate();
        await wrapper.find('#name').setValue('Sunday Crew');

        const pills = wrapper.findAll('[data-testid="league-pill"]');
        await pills[0].trigger('click');   // select EPL
        await pills[0].trigger('click');   // deselect EPL

        await wrapper.find('[data-testid="submit"]').trigger('click');
        expect(axios.post).not.toHaveBeenCalled();
    });
});