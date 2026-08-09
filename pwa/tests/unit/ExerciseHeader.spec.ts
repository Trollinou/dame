import { describe, expect, test } from 'vitest';
import { mount } from '@vue/test-utils';
import ExerciseHeader from '@/components/shared/ExerciseHeader.vue';

describe('ExerciseHeader.vue', () => {
  test('renders unified 2-panel header correctly', () => {
    const wrapper = mount(ExerciseHeader, {
      props: {
        title: "T8 - Vision'checs",
        typeLabel: "Vision'checs",
        chapitreNiveauLabel: "Matérialité // Niveau 1",
        consigne: "Observez les 4 diagrammes ci-dessous.",
        stepBadgeText: "Diagramme 1 / 4"
      }
    });

    expect(wrapper.find('.meta-title').text()).toBe("T8 - Vision'checs");
    expect(wrapper.find('.meta-type').text()).toBe("Vision'checs");
    expect(wrapper.find('.meta-chapitre-niveau').text()).toBe("Matérialité // Niveau 1");
    expect(wrapper.find('.consigne-text').text()).toBe("Observez les 4 diagrammes ci-dessous.");
    expect(wrapper.find('.step-badge').text()).toBe("Diagramme 1 / 4");
  });
});
