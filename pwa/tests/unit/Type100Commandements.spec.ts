import { describe, expect, test, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { VueQueryPlugin } from '@tanstack/vue-query';
import { queryClient } from '@/queryClient';
import Type100Commandements from '@/views/types/Type100Commandements.vue';

// Mock QcmViewer to easily trigger success emit
vi.mock('@/components/shared/QcmViewer.vue', () => ({
  default: {
    name: 'QcmViewer',
    props: ['question', 'choix', 'bonneReponse', 'shapes', 'fen', 'hideQuestion'],
    emits: ['success'],
    template: `
      <div class="mock-qcm-viewer">
        <button class="mock-success-btn" @click="$emit('success')">Validate</button>
      </div>
    `
  }
}));

describe('Type100Commandements.vue', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  test('handles legacy single QCM format correctly', async () => {
    const wrapper = mount(Type100Commandements, {
      global: {
        plugins: [[VueQueryPlugin, { queryClient }]]
      },
      props: {
        id: 101,
        config: {
          question: "Quel est le premier réflexe ?",
          reponses: ["Contrôler le centre", "Avancer la Dame", "Pions des bords"],
          bonne_reponse: 0
        }
      }
    });

    expect(wrapper.find('.consigne-text').text()).toBe("Quel est le premier réflexe ?");
    expect(wrapper.find('.step-badge').text()).toContain('Question 1 / 1');

    await wrapper.find('.mock-success-btn').trigger('click');
    expect(wrapper.emitted('success')).toBeTruthy();
  });

  test('handles series of QCMs correctly (qcms array)', async () => {
    const wrapper = mount(Type100Commandements, {
      global: {
        plugins: [[VueQueryPlugin, { queryClient }]]
      },
      props: {
        id: 102,
        config: {
          qcms: [
            {
              question: "Question 1",
              reponses: ["Ans 1", "Ans 2"],
              bonne_reponse: 0
            },
            {
              question: "Question 2",
              reponses: ["Ans A", "Ans B"],
              bonne_reponse: 1
            }
          ]
        }
      }
    });

    // QCM 1
    expect(wrapper.find('.step-badge').text()).toContain('Question 1 / 2');
    expect(wrapper.find('.consigne-text').text()).toBe("Question 1");

    // Click validate QCM 1 -> should advance to QCM 2 without emitting success yet
    await wrapper.find('.mock-success-btn').trigger('click');
    expect(wrapper.emitted('success')).toBeFalsy();

    // QCM 2
    expect(wrapper.find('.step-badge').text()).toContain('Question 2 / 2');
    expect(wrapper.find('.consigne-text').text()).toBe("Question 2");

    // Click validate QCM 2 -> last QCM, should emit success
    await wrapper.find('.mock-success-btn').trigger('click');
    expect(wrapper.emitted('success')).toBeTruthy();
  });
});
