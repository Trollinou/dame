document.addEventListener('DOMContentLoaded', () => {
	const wrapper = document.getElementById('benevolat-dates-wrapper');
	if (!wrapper) {
		return;
	}

	// Add Date
	const addDateBtn = document.getElementById('add-benevolat-date');
	if (addDateBtn) {
		addDateBtn.addEventListener('click', () => {
			const dateIndex = wrapper.querySelectorAll(
				'.benevolat-date-group'
			).length;
			const newDateGroup = document.createElement('div');
			newDateGroup.className = 'benevolat-date-group';
			newDateGroup.innerHTML = `
				<hr>
				<h4>Date ${dateIndex + 1}</h4>
				<p>
					<label for="benevolat_date_${dateIndex}">Date:</label>
					<input type="date" id="benevolat_date_${dateIndex}" name="_dame_benevolat_data[${dateIndex}][date]" value="" class="benevolat-date-input">
					<button type="button" class="button remove-benevolat-date">Supprimer cette date</button>
				</p>
				<div class="benevolat-time-slots-wrapper"></div>
				<button type="button" class="button add-benevolat-time-slot">Ajouter une plage horaire</button>
			`;
			wrapper.appendChild(newDateGroup);
		});
	}

	// Event Delegation
	wrapper.addEventListener('click', (e) => {
		// Remove Date
		if (e.target.closest('.remove-benevolat-date')) {
			const group = e.target.closest('.benevolat-date-group');
			if (group) {
				group.remove();
				// Re-index h4 titles
				wrapper
					.querySelectorAll('.benevolat-date-group')
					.forEach((dateGroup, index) => {
						const title = dateGroup.querySelector('h4');
						if (title) {
							title.textContent = `Date ${index + 1}`;
						}
					});
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
			const timeSlotsWrapper = dateGroup.querySelector(
				'.benevolat-time-slots-wrapper'
			);
			if (!timeSlotsWrapper) {
				return;
			}

			const slots = timeSlotsWrapper.querySelectorAll(
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

			const newTimeSlot = document.createElement('div');
			newTimeSlot.className = 'benevolat-time-slot-group';
			newTimeSlot.innerHTML = `
				<label>Plage horaire:</label>
				<input type="time" name="_dame_benevolat_data[${dateIndex}][time_slots][${timeIndex}][start]" value="${previousEndTime}" step="900">
				<span>-</span>
				<input type="time" name="_dame_benevolat_data[${dateIndex}][time_slots][${timeIndex}][end]" value="" step="900">
				<button type="button" class="button remove-benevolat-time-slot">Supprimer</button>
			`;
			timeSlotsWrapper.appendChild(newTimeSlot);
			return;
		}

		// Remove Time Slot
		if (e.target.closest('.remove-benevolat-time-slot')) {
			const slot = e.target.closest('.benevolat-time-slot-group');
			if (slot) {
				slot.remove();
			}
		}
	});
});
