import { useRouter } from 'vue-router';

/**
 * Composable pour intercepter les clics sur les liens internes dans le contenu HTML (v-html)
 * et rediriger vers la vue GenericPage interne au lieu d'ouvrir le navigateur.
 */
export function useInternalLinks() {
  const router = useRouter();

  // Liste des domaines considérés comme internes
  const internalDomains = [
    'www.echiquier-ledonien.fr',
    'echecs.local',
    window.location.hostname
  ];

  /**
   * Handler à attacher sur l'événement @click du conteneur v-html
   */
  const handleInternalLinks = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    
    // 1. Gestion des éléments avec data-path (Boutons injectés)
    const clickableWithData = target.closest('[data-path]');
    if (clickableWithData) {
      const path = clickableWithData.getAttribute('data-path');
      if (path) {
        event.preventDefault();
        router.push(path);
        return;
      }
    }

    // 2. Gestion des liens <a> classiques
    const anchor = target.closest('a');

    if (anchor && anchor.href) {
      try {
        const url = new URL(anchor.href);

        // Vérifie si le hostname appartient à la liste des domaines internes
        const isInternal = internalDomains.some(domain => 
          url.hostname === domain || url.hostname.endsWith('.' + domain)
        );

        if (isInternal) {
          // Empêche l'ouverture du lien par le navigateur
          event.preventDefault();

          // Extraction du slug (dernier segment non vide du chemin)
          const pathSegments = url.pathname.split('/').filter(segment => segment.length > 0);
          const slug = pathSegments[pathSegments.length - 1];

          if (slug) {
            router.push({ name: 'GenericPage', params: { id: slug } });
          }
        }
      } catch (e) {
        console.error("Erreur lors de l'analyse de l'URL du lien cliqué", e);
      }
    }
  };

  return {
    handleInternalLinks
  };
}
