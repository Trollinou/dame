document.addEventListener('DOMContentLoaded', () => {
	const wrapper = document.getElementById('benevolat-dates-wrapper');
	if (!wrapper) {
		return;
	}

	/**
	 * Re-sort and re-index all dates and their time slots.
	 */
	function refreshBenevolatData() {
		const groups = Array.from(
			wrapper.querySelectorAll('.benevolat-date-group')
		);

		// 1. Sort Date Groups by their date input value
		groups.sort((a, b) => {
			const inputA = a.querySelector('.benevolat-date-input');
			const inputB = b.querySelector('.benevolat-date-input');
			const dateA = inputA?.value || '9999-12-31';
			const dateB = inputB?.value || '9999-12-31';
			return dateA.localeCompare(dateB);
		});

		// 2. Clear and re-append sorted groups, then fix indices
		groups.forEach((dateGroup, dateIndex) => {
			wrapper.appendChild(dateGroup);

			// Update Date Title
			const titleEl = dateGroup.querySelector('h4');
			if (titleEl) {
				titleEl.textContent = `Date ${dateIndex + 1}`;
			}

			// Update Date Input Name & ID
			const dateInput = dateGroup.querySelector('.benevolat-date-input');
			if (dateInput) {
				dateInput.id = `benevolat_date_${dateIndex}`;
				dateInput.name = `_dame_benevolat_data[${dateIndex}][date]`;
			}

			const label = dateGroup.querySelector(
				'label[for^="benevolat_date_"]'
			);
			if (label) {
				label.htmlFor = `benevolat_date_${dateIndex}`;
			}

			// 3. Sort Time Slots within this date group
			const slotsWrapper = dateGroup.querySelector(
				'.benevolat-time-slots-wrapper'
			);
			if (slotsWrapper) {
				const slots = Array.from(
					slotsWrapper.querySelectorAll('.benevolat-time-slot-group')
				);

				slots.sort((a, b) => {
					const firstInputA = a.querySelector('input[type="time"]');
					const firstInputB = b.querySelector('input[type="time"]');
					const timeA = firstInputA?.value || '23:59';
					const timeB = firstInputB?.value || '23:59';
					return timeA.localeCompare(timeB);
				});

				slots.forEach((slot, timeIndex) => {
					slotsWrapper.appendChild(slot);

					const timeInputs =
						slot.querySelectorAll('input[type="time"]');
					if (timeInputs[0]) {
						timeInputs[0].name = `_dame_benevolat_data[${dateIndex}][time_slots][${timeIndex}][start]`;
					}
					if (timeInputs[1]) {
						timeInputs[1].name = `_dame_benevolat_data[${dateIndex}][time_slots][${timeIndex}][end]`;
					}
				});
			}
		});
	}

	// Add Date
	const addDateBtn = document.getElementById('add-benevolat-date');
	if (addDateBtn) {
		addDateBtn.addEventListener('click', () => {
			const dateIndex = wrapper.querySelectorAll(
				'.benevolat-date-group'
			).length;

			let defaultDate = '';
			const dateInputs = Array.from(
				wrapper.querySelectorAll('.benevolat-date-input')
			);
			if (dateInputs.length > 0) {
				let maxDateVal = '';
				dateInputs.forEach((input) => {
					const val = input.value;
					if (val && (!maxDateVal || val > maxDateVal)) {
						maxDateVal = val;
					}
				});

				if (maxDateVal) {
					const parts = maxDateVal.split('-');
					if (parts.length === 3) {
						const dateObj = new Date(
							Date.UTC(
								parseInt(parts[0], 10),
								parseInt(parts[1], 10) - 1,
								parseInt(parts[2], 10)
							)
						);
						dateObj.setUTCDate(dateObj.getUTCDate() + 1);
						const year = dateObj.getUTCFullYear();
						const month = String(
							dateObj.getUTCMonth() + 1
						).padStart(2, '0');
						const day = String(dateObj.getUTCDate()).padStart(
							2,
							'0'
						);
						defaultDate = `${year}-${month}-${day}`;
					}
				}
			}

			const template = document.createElement('div');
			template.className = 'benevolat-date-group';
			template.innerHTML = `
				<hr>
				<h4>Date ${dateIndex + 1}</h4>
				<p>
					<label for="benevolat_date_${dateIndex}">Date:</label>
					<input type="date" id="benevolat_date_${dateIndex}" name="_dame_benevolat_data[${dateIndex}][date]" value="${defaultDate}" class="benevolat-date-input">
					<button type="button" class="button remove-benevolat-date">Supprimer cette date</button>
				</p>
				<div class="benevolat-time-slots-wrapper"></div>
				<button type="button" class="button add-benevolat-time-slot">Ajouter une plage horaire</button>
			`;
			wrapper.appendChild(template);
		});
	}

	// Event Delegation on wrapper
	wrapper.addEventListener('change', (e) => {
		if (
			e.target.classList.contains('benevolat-date-input') ||
			e.target.classList.contains('benevolat-time-start')
		) {
			refreshBenevolatData();
		}
	});

	wrapper.addEventListener('click', (e) => {
		// Remove Date
		if (e.target.closest('.remove-benevolat-date')) {
			const group = e.target.closest('.benevolat-date-group');
			if (group) {
				group.remove();
				refreshBenevolatData();
			}
			return;
		}

		// Add Time Slot
		if (e.target.closest('.add-benevolat-time-slot')) {
			const dateGroup = e.target.closest('.benevolat-date-group');
			if (!dateGroup) {
				return;
			}

			const groups = Array.from(
				wrapper.querySelectorAll('.benevolat-date-group')
			);
			const dateIndex = groups.indexOf(dateGroup);
			const slotsWrapper = dateGroup.querySelector(
				'.benevolat-time-slots-wrapper'
			);
			if (!slotsWrapper) {
				return;
			}

			const slots = slotsWrapper.querySelectorAll(
				'.benevolat-time-slot-group'
			);
			const timeIndex = slots.length;

			let previousEndTime = '';
			if (timeIndex > 0) {
				const lastSlot = slots[slots.length - 1];
				const timeInputs =
					lastSlot.querySelectorAll('input[type="time"]');
				if (timeInputs.length >= 2) {
					previousEndTime = timeInputs[1].value;
				}
			}

			const slotDiv = document.createElement('div');
			slotDiv.className = 'benevolat-time-slot-group';
			slotDiv.innerHTML = `
				<label>Plage horaire:</label>
				<input type="time" name="_dame_benevolat_data[${dateIndex}][time_slots][${timeIndex}][start]" value="${previousEndTime}" step="900" class="benevolat-time-start">
				<span>-</span>
				<input type="time" name="_dame_benevolat_data[${dateIndex}][time_slots][${timeIndex}][end]" value="" step="900" class="benevolat-time-end">
				<button type="button" class="button remove-benevolat-time-slot">Supprimer</button>
			`;
			slotsWrapper.appendChild(slotDiv);
			return;
		}

		// Remove Time Slot
		if (e.target.closest('.remove-benevolat-time-slot')) {
			const slot = e.target.closest('.benevolat-time-slot-group');
			if (slot) {
				slot.remove();
				refreshBenevolatData();
			}
		}
	});
});
