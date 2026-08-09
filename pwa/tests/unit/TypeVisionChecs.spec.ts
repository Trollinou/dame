import { describe, expect, test, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { VueQueryPlugin } from '@tanstack/vue-query';
import { queryClient } from '@/queryClient';
import TypeVisionChecs from '@/views/types/TypeVisionChecs.vue';
import { parseFenPieces, getPieceColumns, getActiveColorFromFen } from '@/utils/fenUtils';

// Mock eg-chessboard to easily trigger square clicks and test board interactions
vi.mock('eg-chessboard/vue', () => ({
  default: {
    name: 'EgChessboard',
    props: ['diagram', 'playerColor', 'preserveShapesOnPositionChange', 'stockfishConfig'],
    emits: ['square-click', 'board-created'],
    template: `
      <div class="mock-eg-chessboard">
        <span class="mock-fen">{{ diagram ? diagram.fen : '' }}</span>
        <button
          class="mock-square-btn"
          v-for="sq in ['e2', 'e4', 'f1', 'b5', 'g1', 'f3', 'd5']"
          :key="sq"
          @click="$emit('square-click', sq)"
        >
          Click {{ sq }}
        </button>
      </div>
    `
  }
}));

describe('fenUtils.ts', () => {
  test('parseFenPieces extracts pieces correctly from standard initial position', () => {
    const fen = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
    const pieces = parseFenPieces(fen);

    expect(pieces.length).toBe(32);
    const e2Pawn = pieces.find((p) => p.square === 'e2');
    expect(e2Pawn).toBeDefined();
    expect(e2Pawn?.role).toBe('pawn');
    expect(e2Pawn?.color).toBe('white');

    const e8King = pieces.find((p) => p.square === 'e8');
    expect(e8King).toBeDefined();
    expect(e8King?.role).toBe('king');
    expect(e8King?.color).toBe('black');
  });

  test('getPieceColumns formats 1 column when total pieces <= 4', () => {
    const fen = '4k3/8/8/4P3/8/8/8/4K3 w - - 0 1';
    const pieces = parseFenPieces(fen);
    expect(pieces.length).toBe(3);

    const cols = getPieceColumns(pieces);
    expect(cols.isTwoColumns).toBe(false);
    expect(cols.col1.length).toBe(3);
    expect(cols.col2.length).toBe(0);
  });

  test('getPieceColumns formats 2 columns when total pieces > 4', () => {
    const fen = 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq - 2 3';
    const pieces = parseFenPieces(fen);
    expect(pieces.length).toBe(32);

    const cols = getPieceColumns(pieces);
    expect(cols.isTwoColumns).toBe(true);
    expect(cols.col1.length).toBe(4);
    expect(cols.col2.length).toBe(28);
  });

  test('getActiveColorFromFen detects active color turn', () => {
    expect(getActiveColorFromFen('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1')).toBe('white');
    expect(getActiveColorFromFen('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR b KQkq - 0 1')).toBe('black');
  });
});

describe('TypeVisionChecs.vue', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.useFakeTimers();
  });

  test('renders 4 diagrams and progresses through them on correct move selection', async () => {
    const wrapper = mount(TypeVisionChecs, {
      global: {
        plugins: [[VueQueryPlugin, { queryClient }]]
      },
      props: {
        id: 801,
        config: {
          consigne: 'Observez les 4 diagrammes ci-dessous.',
          diagrammes: [
            {
              fen: 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1',
              couleur_joueur: 'white',
              shapes: [{ orig: 'e2', dest: 'e4', brush: 'blue' }]
            },
            {
              fen: 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq - 2 3',
              couleur_joueur: 'white',
              shapes: [{ orig: 'f1', dest: 'b5', brush: 'blue' }]
            },
            {
              fen: 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq - 0 2',
              couleur_joueur: 'white',
              shapes: [{ orig: 'g1', dest: 'f3', brush: 'blue' }]
            },
            {
              fen: 'rnbqkbnr/ppp1pppp/8/3p4/4P3/8/PPPP1PPP/RNBQKBNR w KQkq - 0 2',
              couleur_joueur: 'white',
              shapes: [{ orig: 'e4', dest: 'd5', brush: 'blue' }]
            }
          ]
        }
      }
    });

    expect(wrapper.text()).toContain('Diagramme 1 / 4');

    // Diagramme 1: e2 -> e4
    const buttons = wrapper.findAll('.mock-square-btn');
    const btnE2 = buttons.find((b) => b.text().includes('e2'));
    const btnE4 = buttons.find((b) => b.text().includes('e4'));

    await btnE2?.trigger('click');
    await btnE4?.trigger('click');

    // Fast-forward timers for revelation delay (400ms move + 1400ms transition)
    vi.advanceTimersByTime(2000);
    await wrapper.vm.$nextTick();

    // Now on Diagramme 2
    expect(wrapper.text()).toContain('Diagramme 2 / 4');

    // Diagramme 2: f1 -> b5
    const btnF1 = buttons.find((b) => b.text().includes('f1'));
    const btnB5 = buttons.find((b) => b.text().includes('b5'));

    await btnF1?.trigger('click');
    await btnB5?.trigger('click');

    vi.advanceTimersByTime(2000);
    await wrapper.vm.$nextTick();

    // Diagramme 3
    expect(wrapper.text()).toContain('Diagramme 3 / 4');

    // Diagramme 3: g1 -> f3
    const btnG1 = buttons.find((b) => b.text().includes('g1'));
    const btnF3 = buttons.find((b) => b.text().includes('f3'));

    await btnG1?.trigger('click');
    await btnF3?.trigger('click');

    vi.advanceTimersByTime(2000);
    await wrapper.vm.$nextTick();

    // Diagramme 4
    expect(wrapper.text()).toContain('Diagramme 4 / 4');

    // Diagramme 4: e4 -> d5
    const btnD5 = buttons.find((b) => b.text().includes('d5'));

    await btnE4?.trigger('click');
    await btnD5?.trigger('click');

    vi.advanceTimersByTime(2000);
    await wrapper.vm.$nextTick();

    // Exercise finished -> success emitted
    expect(wrapper.emitted('success')).toBeTruthy();
  });
});
