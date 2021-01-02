window.addEventListener('DOMContentLoaded', function() {
	var el_act = null;	//current drag elem
	var el_last = null;	//last element with a highlight border
	var dragging = false;
	var i, l;

	var translate_form = document.getElementById('translate_labels');
	if (!translate_form)
		return;

	var rows = translate_form.getElementsByClassName('translate-row');
	for (i = 0, l = rows.length; i < l; i++) {
		initrow(rows[i]);
	}
	var submitButton = document.getElementById('translate_save');
	translate_form.addEventListener('change', function (e) {
		formChanged();
	});

	function formChanged() {
		submitButton.classList.add('btn-danger');
	}

	function initrow(row) {
		var handles = row.getElementsByClassName('move');
		var adders = row.getElementsByClassName('add');
		var removers = row.getElementsByClassName('del');
		if (handles.length > 0)
			inithandle(handles[0]);
		if (adders.length > 0)
			initadder(adders[0]);
		if (removers.length > 0)
			initremover(removers[0]);
	}

	function inithandle(obj) {
		obj.setAttribute('draggable', 'true');
		obj.addEventListener('dragstart', function (e) {
			e.dataTransfer.effectAllowed = 'move';
			el_act = this.parentNode;
			el_act.style.opacity = '0.5';
			dragging = true;
		});

		obj.addEventListener('dragend', function (e) {
			dragging = false;
			if (el_act)
				el_act.style.opacity = '';
			if (el_last)
				el_last.className = 'translate-row';
		});

		obj.parentNode.addEventListener('dragover', function (e) {
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

		obj.parentNode.addEventListener('drop', function (e) {
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
	function initadder(obj) {
		obj.addEventListener('click', function (e) {
			e.stopPropagation();
			e.preventDefault();
			var refnode = this.parentNode.nextSibling;
			var newnode = this.parentNode.cloneNode(true);
			var inputs = newnode.getElementsByTagName('input');
			var textareas = newnode.getElementsByTagName('textarea');
			var newkey = '_newkey' + newindex++;
			var oname = inputs[0].getAttribute('name');
			var key = oname.substring(oname.lastIndexOf('[') + 1, oname.lastIndexOf(']'));
			var nname = 'tx_translatelocallang_tools_translatelocallangm1[keys]['+newkey+']';
			inputs[0].setAttribute('name', nname);
			inputs[0].value = '';

			for (i = 0; i < textareas.length; i++) {
				oname = textareas[i].getAttribute('name');
				nname = oname.replace('][' + key + '][', '][' + newkey+ '][');
				textareas[i].setAttribute('name', nname);
				textareas[i].value = '';
			}
			initrow(newnode);
			this.parentNode.parentNode.insertBefore(newnode, refnode);
		});
	}

	function initremover(obj) {
		obj.addEventListener('click', function (e) {
			this.parentNode.parentNode.removeChild(obj.parentNode);
			formChanged();
		});
	}
});
