import { ref, computed } from 'vue';

export function useAgendaSearch() {
  const selectedSegment = ref('actualites');
  const searchQuery = ref('');

  const pageTitle = computed(() => {
    if (selectedSegment.value === 'actualites') return 'Actualités';
    if (selectedSegment.value === 'agenda') return 'Agenda';
    if (selectedSegment.value === 'tournois') return 'Tournois';
    if (selectedSegment.value === 'benevolat') return 'Bénévolat';
    return 'Le Club';
  });

  const searchPlaceholder = computed(() => {
    if (selectedSegment.value === 'tournois') return 'Rechercher un tournoi...';
    if (selectedSegment.value === 'benevolat') return 'Rechercher un appel...';
    if (selectedSegment.value === 'actualites') return 'Rechercher une actualité...';
    return 'Rechercher un événement...';
  });

  const onSegmentChange = (val: string, onTabSwitch?: () => void) => {
    selectedSegment.value = val;
    searchQuery.value = '';
    if (onTabSwitch) onTabSwitch();
  };

  return {
    selectedSegment,
    searchQuery,
    pageTitle,
    searchPlaceholder,
    onSegmentChange,
  };
}
