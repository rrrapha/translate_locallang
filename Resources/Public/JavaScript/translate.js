window.addEventListener('DOMContentLoaded', function() {

	// Add event listeners to filter selects to submit the form on change
	const filterSelects = document.querySelectorAll('select[data-name="extkey"], select[data-name="file"], select[data-name="langKeys"]');

	filterSelects.forEach(select => {
		select.addEventListener('change', () => {
			const form = select.closest('form');
			form.submit();
		});
	});

	var el_act = null;	//current drag elem
	var el_last = null;	//last element with a highlight border
	var dragging = false;
	var i, l;

	var translate_form = document.getElementById('translate_labels');
	if (!translate_form)
		return;

	var rows = translate_form.getElementsByClassName('translate-row');
	for (i = 1, l = rows.length; i < l; i++) {
		initrow(rows[i]);
	}
	var submitButton = document.getElementsByName('translate_save')[0];
	translate_form.addEventListener('change', function (e) {
		formChanged();
	});

	if (submitButton.classList.contains('btn-danger')) {
		submitButton.classList.remove('btn-default'); // needed in TYPO3 12
	}

	function formChanged() {
		submitButton.classList.add('btn-danger');
		submitButton.classList.remove('btn-default');
	}

	function initrow(row) {
		var parents = row.getElementsByClassName('add');
		if (parents.length > 0)
			initAdd(row, parents[0].firstElementChild);
		parents = row.getElementsByClassName('del');
		if (parents.length > 0)
			initDel(row, parents[0].firstElementChild);
		parents = row.getElementsByClassName('move');
		if (parents.length > 0)
			initMove(row, parents[0].firstElementChild);
	}

	function initMove(row, button) {
		button.setAttribute('draggable', 'true');
		button.addEventListener('dragstart', function (e) {
			e.dataTransfer.effectAllowed = 'move';
			el_act = row;
			el_act.style.opacity = '0.5';
			dragging = true;
		});

		button.addEventListener('dragend', function (e) {
			dragging = false;
			if (el_act)
				el_act.style.opacity = '';
			if (el_last)
				el_last.className = 'translate-row';
		});

		row.addEventListener('dragover', function (e) {
			e.preventDefault();
			if (!dragging)
				return false;
			e.dataTransfer.dropEffect = 'move';

			var el_next = this.nextElementSibling;
			var rect = this.getBoundingClientRect();
			var vcenter = (rect.top + rect.bottom) / 2;

			if (el_last)
				el_last.className = 'translate-row';
			if (el_next)
				el_last = (e.clientY < vcenter) ? this : el_next;
			else
				el_last = this;
			el_last.className = 'translate-row over';
		});

		row.addEventListener('drop', function (e) {
			e.stopPropagation();
			e.preventDefault();
			if (!dragging)
				return false;
			var rect = this.getBoundingClientRect();
			var vcenter = (rect.top + rect.bottom) / 2;
			if (e.clientY < vcenter)
				this.parentNode.insertBefore(el_act, this); //insert before
			else
				this.parentNode.insertBefore(el_act, this.nextSibling); //insert after
			formChanged();
		});
	}

	var newindex = 0;
	function initAdd(row, button) {
		button.addEventListener('click', function (e) {
			e.stopPropagation();
			e.preventDefault();
			var refnode = row.nextSibling;
			var newnode = row.cloneNode(true);
			var inputs = newnode.getElementsByTagName('input');
			var textareas = newnode.getElementsByTagName('textarea');
			var newkey = '_newkey' + newindex++;
			var oname = inputs[0].getAttribute('name');
			var key = oname.substring(oname.lastIndexOf('[') + 1, oname.lastIndexOf(']'));
			var nname = oname.replace('[' + key + ']', '[' + newkey+ ']');
			inputs[0].setAttribute('name', nname);
			inputs[0].value = '';

			for (i = 0; i < textareas.length; i++) {
				oname = textareas[i].getAttribute('name');
				nname = oname.replace('[' + key + '][', '[' + newkey+ '][');
				textareas[i].setAttribute('name', nname);
				textareas[i].value = '';
			}
			initrow(newnode);
			row.parentNode.insertBefore(newnode, refnode);
			inputs[0].focus();
		});
	}

	function initDel(row, button) {
		button.addEventListener('click', function (e) {
			row.parentNode.removeChild(row);
			formChanged();
		});
	}
});
