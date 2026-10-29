/**
 * Admin Ticketing & Check-in Manager
 * Dynamic instant filtering, multi-select tiers, AJAX check-in, Sharly Chess export & modals.
 *
 * @package
 */

interface DameTicketingConfig {
  ajaxUrl: string;
  nonce: string;
  campaignId: number;
  totalItems: number;
  checkedInCount: number;
}

declare const dame_ticketing_data: DameTicketingConfig;

document.addEventListener('DOMContentLoaded', () => {
  const container = document.querySelector('.dame-ticketing-wrap') as HTMLElement | null;
  if (!container || typeof dame_ticketing_data === 'undefined') {
    return;
  }

  const { ajaxUrl, nonce, campaignId } = dame_ticketing_data;
  const currentTotalItems = dame_ticketing_data.totalItems;
  let currentCheckedIn = dame_ticketing_data.checkedInCount;

  // DOM Elements
  const searchInput = document.getElementById('dame-ticketing-search') as HTMLInputElement | null;
  const statusButtons = document.querySelectorAll<HTMLButtonElement>('.dame-status-btn');
  const tierCheckboxes = document.querySelectorAll<HTMLInputElement>('.dame-tier-checkbox');
  const tierSearchInput = document.getElementById('dame-tier-search') as HTMLInputElement | null;
  const selectAllTiersBtn = document.getElementById('dame-select-all-tiers') as HTMLButtonElement | null;
  const clearAllTiersBtn = document.getElementById('dame-clear-all-tiers') as HTMLButtonElement | null;
  const tierDropdownTrigger = document.getElementById('dame-tier-dropdown-trigger') as HTMLButtonElement | null;
  const tierDropdownMenu = document.getElementById('dame-tier-dropdown-menu') as HTMLElement | null;
  const tierBadge = document.getElementById('dame-tier-badge') as HTMLElement | null;

  const exportTrigger = document.getElementById('dame-export-trigger') as HTMLButtonElement | null;
  const exportMenu = document.getElementById('dame-export-menu') as HTMLElement | null;
  const exportFilteredLink = document.getElementById('dame-export-filtered') as HTMLAnchorElement | null;
  const exportCheckedLink = document.getElementById('dame-export-checked') as HTMLAnchorElement | null;
  const exportAllLink = document.getElementById('dame-export-all') as HTMLAnchorElement | null;

  const toolsTrigger = document.getElementById('dame-tools-trigger') as HTMLButtonElement | null;
  const toolsMenu = document.getElementById('dame-tools-menu') as HTMLElement | null;

  // Modals
  const openOnsiteModalBtn = document.getElementById('dame-open-onsite-modal') as HTMLButtonElement | null;
  const onsiteModal = document.getElementById('dame-onsite-modal') as HTMLElement | null;
  const closeOnsiteModalBtn = document.getElementById('dame-close-onsite-modal') as HTMLButtonElement | null;
  const cancelOnsiteModalBtn = document.getElementById('dame-cancel-onsite-modal') as HTMLButtonElement | null;

  const openContactsModalBtn = document.getElementById('dame-open-contacts-modal') as HTMLButtonElement | null;
  const contactsModal = document.getElementById('dame-contacts-modal') as HTMLElement | null;
  const closeContactsModalBtn = document.getElementById('dame-close-contacts-modal') as HTMLButtonElement | null;
  const cancelContactsModalBtn = document.getElementById('dame-cancel-contacts-modal') as HTMLButtonElement | null;

  // KPIs
  const kpiCheckedIn = document.getElementById('dame-kpi-checked-in') as HTMLElement | null;
  const kpiVisibleCount = document.getElementById('dame-kpi-visible-count') as HTMLElement | null;

  // Table rows
  const table = document.getElementById('dame-ticketing-table') as HTMLTableElement | null;
  const rows = Array.from(document.querySelectorAll<HTMLTableRowElement>('.dame-participant-row'));
  const noMatchRow = document.getElementById('dame-no-match-row') as HTMLTableRowElement | null;

  // State
  let currentStatusFilter: 'all' | '1' | '0' = 'all';
  let searchQuery = '';
  const selectedTiers: Set<string> = new Set();

  // Initialize selected tiers with all available
  tierCheckboxes.forEach(cb => {
    selectedTiers.add(cb.value);
  });

  // -------------------------------------------------------------
  // Dropdown toggles & outside click handling
  // -------------------------------------------------------------
  function closeAllDropdowns() {
    tierDropdownMenu?.classList.remove('open');
    exportMenu?.classList.remove('open');
    toolsMenu?.classList.remove('open');
  }

  tierDropdownTrigger?.addEventListener('click', (e) => {
    e.stopPropagation();
    const isOpen = tierDropdownMenu?.classList.contains('open');
    closeAllDropdowns();
    if (!isOpen) {
      tierDropdownMenu?.classList.add('open');
      tierSearchInput?.focus();
    }
  });

  tierDropdownMenu?.addEventListener('click', (e) => {
    e.stopPropagation();
  });

  exportTrigger?.addEventListener('click', (e) => {
    e.stopPropagation();
    const isOpen = exportMenu?.classList.contains('open');
    closeAllDropdowns();
    if (!isOpen) {
      exportMenu?.classList.add('open');
    }
  });

  toolsTrigger?.addEventListener('click', (e) => {
    e.stopPropagation();
    const isOpen = toolsMenu?.classList.contains('open');
    closeAllDropdowns();
    if (!isOpen) {
      toolsMenu?.classList.add('open');
    }
  });

  document.addEventListener('click', () => {
    closeAllDropdowns();
  });

  // -------------------------------------------------------------
  // Modals management
  // -------------------------------------------------------------
  function openModal(modal: HTMLElement | null) {
    if (!modal) {return;}
    closeAllDropdowns();
    modal.classList.add('open');
    document.body.style.overscrollBehavior = 'contain';
  }

  function closeModal(modal: HTMLElement | null) {
    if (!modal) {return;}
    modal.classList.remove('open');
    document.body.style.overscrollBehavior = '';
  }

  openOnsiteModalBtn?.addEventListener('click', () => openModal(onsiteModal));
  closeOnsiteModalBtn?.addEventListener('click', () => closeModal(onsiteModal));
  cancelOnsiteModalBtn?.addEventListener('click', () => closeModal(onsiteModal));

  openContactsModalBtn?.addEventListener('click', () => openModal(contactsModal));
  closeContactsModalBtn?.addEventListener('click', () => closeModal(contactsModal));
  cancelContactsModalBtn?.addEventListener('click', () => closeModal(contactsModal));

  [onsiteModal, contactsModal].forEach(modal => {
    modal?.addEventListener('click', (e) => {
      if (e.target === modal) {
        closeModal(modal);
      }
    });
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeAllDropdowns();
      closeModal(onsiteModal);
      closeModal(contactsModal);
    }
  });

  // -------------------------------------------------------------
  // Filtering Logic
  // -------------------------------------------------------------
  function updateFilter() {
    let visibleCount = 0;
    let visibleCheckedInCount = 0;
    const normalizedSearch = searchQuery.toLowerCase().trim();

    rows.forEach(row => {
      const isCheckedIn = row.dataset.checkedIn === '1';
      const tierName = row.dataset.tier || '';
      const searchText = (row.dataset.search || '').toLowerCase();

      // Check Status
      let matchStatus = true;
      if (currentStatusFilter === '1') {
        matchStatus = isCheckedIn;
      } else if (currentStatusFilter === '0') {
        matchStatus = !isCheckedIn;
      }

      // Check Tier
      const matchTier = selectedTiers.size === 0 || selectedTiers.has(tierName);

      // Check Search text
      const matchSearch = !normalizedSearch || searchText.includes(normalizedSearch);

      const isVisible = matchStatus && matchTier && matchSearch;
      row.style.display = isVisible ? '' : 'none';

      if (isVisible) {
        visibleCount++;
        if (isCheckedIn) {
          visibleCheckedInCount++;
        }
      }
    });

    if (noMatchRow) {
      noMatchRow.style.display = visibleCount === 0 ? '' : 'none';
    }

    // Update KPI visible badge
    if (kpiVisibleCount) {
      kpiVisibleCount.textContent = `${visibleCount}`;
    }

    // Update Tier dropdown badge
    if (tierBadge) {
      const totalTiers = tierCheckboxes.length;
      if (selectedTiers.size === totalTiers) {
        tierBadge.textContent = 'Tous';
      } else {
        tierBadge.textContent = `${selectedTiers.size}/${totalTiers}`;
      }
    }

    // Update Sharly Export Links
    updateExportLinks(visibleCount, visibleCheckedInCount);
  }

  function updateExportLinks(visibleCount: number, visibleCheckedInCount: number) {
    if (exportTrigger) {
      exportTrigger.innerHTML = `📥 Exporter Sharly (${visibleCount}) <span class="dashicons dashicons-arrow-down-alt2" style="font-size:14px; width:14px; height:14px; margin-left:4px;"></span>`;
    }

    const baseExportUrl = `admin.php?page=dame-ticketing&dame_export_sharly_chess=1&campaign_id=${campaignId}&_wpnonce=${encodeURIComponent(
      (document.getElementById('dame-sharly-nonce') as HTMLInputElement)?.value || ''
    )}`;

    // Build tiers query param
    const allChecked = selectedTiers.size === tierCheckboxes.length;
    let tiersParam = '';
    if (!allChecked && selectedTiers.size > 0) {
      tiersParam = Array.from(selectedTiers).map(t => `&tiers[]=${encodeURIComponent(t)}`).join('');
    }

    if (exportFilteredLink) {
      exportFilteredLink.href = `${baseExportUrl}${tiersParam}`;
      exportFilteredLink.textContent = `📥 Exporter la sélection filtrée (${visibleCount} joueur${visibleCount > 1 ? 's' : ''})`;
    }

    if (exportCheckedLink) {
      exportCheckedLink.href = `${baseExportUrl}&only_checked_in=1${tiersParam}`;
      exportCheckedLink.textContent = `✓ Exporter les pointés filtrés (${visibleCheckedInCount} joueur${visibleCheckedInCount > 1 ? 's' : ''})`;
    }

    if (exportAllLink) {
      exportAllLink.href = baseExportUrl;
      exportAllLink.textContent = `🌐 Exporter l'intégralité (Tous les ${currentTotalItems} inscrits)`;
    }
  }

  // Search input event
  searchInput?.addEventListener('input', () => {
    searchQuery = searchInput.value;
    updateFilter();
  });

  // Status buttons event
  statusButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      statusButtons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      currentStatusFilter = (btn.dataset.status as 'all' | '1' | '0') || 'all';
      updateFilter();
    });
  });

  // Tier checkboxes event
  tierCheckboxes.forEach(cb => {
    cb.addEventListener('change', () => {
      if (cb.checked) {
        selectedTiers.add(cb.value);
      } else {
        selectedTiers.delete(cb.value);
      }
      updateFilter();
    });
  });

  // Select all / clear all tiers
  selectAllTiersBtn?.addEventListener('click', () => {
    tierCheckboxes.forEach(cb => {
      cb.checked = true;
      selectedTiers.add(cb.value);
    });
    updateFilter();
  });

  clearAllTiersBtn?.addEventListener('click', () => {
    tierCheckboxes.forEach(cb => {
      cb.checked = false;
    });
    selectedTiers.clear();
    updateFilter();
  });

  // Tier list quick search
  tierSearchInput?.addEventListener('input', () => {
    const q = tierSearchInput.value.toLowerCase().trim();
    const labels = document.querySelectorAll<HTMLLabelElement>('.dame-dropdown-list label');
    labels.forEach(label => {
      const text = label.textContent?.toLowerCase() || '';
      label.style.display = !q || text.includes(q) ? 'flex' : 'none';
    });
  });

  // -------------------------------------------------------------
  // AJAX Check-in Toggle
  // -------------------------------------------------------------
  container.addEventListener('click', async (e) => {
    const target = e.target as HTMLElement;
    const checkinBtn = target.closest('.dame-checkin-btn') as HTMLButtonElement | null;
    if (!checkinBtn) {
      return;
    }

    e.preventDefault();
    const itemId = checkinBtn.dataset.itemId;
    const row = checkinBtn.closest('.dame-participant-row') as HTMLTableRowElement | null;
    if (!itemId || !row || checkinBtn.classList.contains('is-loading')) {
      return;
    }

    checkinBtn.classList.add('is-loading');

    try {
      const formData = new FormData();
      formData.append('action', 'dame_toggle_checkin_ajax');
      formData.append('item_id', itemId);
      formData.append('campaign_id', campaignId.toString());
      formData.append('_ajax_nonce', nonce);

      const res = await fetch(ajaxUrl, {
        method: 'POST',
        body: formData,
      });

      const json = await res.json();
      if (json.success && json.data) {
        const isNowPresent = json.data.checked_in === 1;

        // Update button state
        checkinBtn.classList.toggle('is-present', isNowPresent);
        checkinBtn.textContent = isNowPresent ? '✓ Présent' : 'Absent';

        // Update row attribute and style
        row.dataset.checkedIn = isNowPresent ? '1' : '0';
        row.classList.toggle('is-checked-in', isNowPresent);

        // Update global counters
        currentCheckedIn = json.data.checked_in_count;
        if (kpiCheckedIn) {
          const pct = currentTotalItems > 0 ? Math.round((currentCheckedIn / currentTotalItems) * 100) : 0;
          kpiCheckedIn.textContent = `${currentCheckedIn} (${pct}%)`;
        }

        // Update filters count on status buttons
        const presentBtnCount = document.querySelector('.dame-status-btn[data-status="1"] .count');
        const waitingBtnCount = document.querySelector('.dame-status-btn[data-status="0"] .count');
        if (presentBtnCount) {presentBtnCount.textContent = `(${currentCheckedIn})`;}
        if (waitingBtnCount) {waitingBtnCount.textContent = `(${currentTotalItems - currentCheckedIn})`;}

        updateFilter();
      } else {
        alert(json.data?.message || 'Erreur lors du pointage.');
      }
    } catch (err) {
      console.error('Checkin AJAX error:', err);
      alert('Erreur réseau lors de la mise à jour du pointage.');
    } finally {
      checkinBtn.classList.remove('is-loading');
    }
  });

  // -------------------------------------------------------------
  // Live Column Sorting
  // -------------------------------------------------------------
  const sortableHeaders = document.querySelectorAll<HTMLTableCellElement>('th.sortable');
  let currentSortCol = 'participant';
  let currentSortAsc = true;

  sortableHeaders.forEach(th => {
    th.addEventListener('click', () => {
      const col = th.dataset.sort;
      if (!col || !table) {return;}

      if (currentSortCol === col) {
        currentSortAsc = !currentSortAsc;
      } else {
        currentSortCol = col;
        currentSortAsc = true;
      }

      // Update sort indicators
      sortableHeaders.forEach(h => {
        const ind = h.querySelector('.sort-indicator');
        if (ind) {ind.textContent = '';}
      });
      const ind = th.querySelector('.sort-indicator');
      if (ind) {
        ind.textContent = currentSortAsc ? ' ▲' : ' ▼';
      }

      // Sort rows
      const tbody = table.querySelector('tbody');
      if (!tbody) {return;}

      const sortedRows = rows.slice().sort((a, b) => {
        let valA = '';
        let valB = '';

        if (col === 'checkin') {
          valA = a.dataset.checkedIn || '0';
          valB = b.dataset.checkedIn || '0';
        } else if (col === 'participant') {
          valA = a.dataset.name || '';
          valB = b.dataset.name || '';
        } else if (col === 'elo') {
          const eloA = parseInt(a.dataset.elo || '0', 10);
          const eloB = parseInt(b.dataset.elo || '0', 10);
          return currentSortAsc ? eloA - eloB : eloB - eloA;
        } else if (col === 'tier') {
          valA = a.dataset.tier || '';
          valB = b.dataset.tier || '';
        } else if (col === 'club') {
          valA = a.dataset.club || '';
          valB = b.dataset.club || '';
        } else if (col === 'amount') {
          const amtA = parseInt(a.dataset.amount || '0', 10);
          const amtB = parseInt(b.dataset.amount || '0', 10);
          return currentSortAsc ? amtA - amtB : amtB - amtA;
        }

        return currentSortAsc ? valA.localeCompare(valB) : valB.localeCompare(valA);
      });

      sortedRows.forEach(r => tbody.appendChild(r));
      if (noMatchRow) {
        tbody.appendChild(noMatchRow);
      }
    });
  });

  // Initial filter run
  updateFilter();
});
