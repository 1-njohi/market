import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';
import { ref } from 'vue';

vi.mock('@inertiajs/vue3', () => {
    const post = vi.fn();
    return {
        Head: { render: () => null },
        Link: { render: () => null },
        useForm: (initial: any) => {
            const data = ref({ ...initial });
            return new Proxy(data, {
                get(target, prop) {
                    if (prop === 'post') return post;
                    if (prop === 'processing') return false;
                    if (prop === 'errors') return {};
                    return (target.value as any)[prop];
                },
                set(target, prop, value) {
                    (target.value as any)[prop] = value;
                    return true;
                },
            });
        },
    };
});

import Confirm from '../Contests/Confirm.vue';

const defaultProps = {
    legs: [],
    min_legs: 5,
    max_legs: 50,
    earliest_kickoff: '2026-08-15T15:00:00Z',
    seasons: ['2026-27', '2027-28'],
};

describe('Confirm page — matchday toggle', () => {
    it('hides matchday fields when toggle is off', () => {
        const wrapper = mount(Confirm, { props: defaultProps });
        expect(wrapper.find('[data-testid="deadline-input"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="season-select"]').exists()).toBe(false);
        expect(wrapper.find('[data-testid="matchday-number-input"]').exists()).toBe(false);
    });

    it('reveals season + matchday number and hides deadline when toggle is on', async () => {
        const wrapper = mount(Confirm, { props: defaultProps });
        await wrapper.find('[data-testid="is-matchday-toggle"]').trigger('click');

        expect(wrapper.find('[data-testid="deadline-input"]').exists()).toBe(false);
        expect(wrapper.find('[data-testid="season-select"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="matchday-number-input"]').exists()).toBe(true);
    });

    it('populates the season select from the seasons prop', async () => {
        const wrapper = mount(Confirm, { props: defaultProps });
        await wrapper.find('[data-testid="is-matchday-toggle"]').trigger('click');

        const options = wrapper.find('[data-testid="season-select"]').findAll('option');
        expect(options).toHaveLength(2);
        expect(options[0].text()).toBe('2026-27');
        expect(options[1].text()).toBe('2027-28');
    });

    it('disables submit when matchday is on but season or number is missing', async () => {
        const wrapper = mount(Confirm, { props: defaultProps });
        await wrapper.find('#name').setValue('MD1 EPL');
        await wrapper.find('[data-testid="is-matchday-toggle"]').trigger('click');

        const submit = wrapper.find('[data-testid="submit"]');
        expect(submit.attributes('disabled')).toBeDefined();

        await wrapper.find('[data-testid="matchday-number-input"]').setValue('1');
        expect(submit.attributes('disabled')).toBeUndefined();
    });
});