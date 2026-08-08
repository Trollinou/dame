import { ref, onMounted, onUnmounted } from 'vue';

export function useBoardOrientation() {
  const isLandscape = ref(window.innerWidth > window.innerHeight);
  const renderKey = ref(0);
  let resizeTimeout: ReturnType<typeof setTimeout> | null = null;

  const updateOrientation = () => {
    if (resizeTimeout) clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
      isLandscape.value = window.innerWidth > window.innerHeight;
      renderKey.value++;
    }, 200);
  };

  onMounted(() => {
    window.addEventListener('resize', updateOrientation);
  });

  onUnmounted(() => {
    window.removeEventListener('resize', updateOrientation);
  });

  return {
    isLandscape,
    renderKey,
    updateOrientation,
  };
}
