import '../css/admin.css';

document.addEventListener('DOMContentLoaded', function() {

    const typologyField = document.getElementById('aatxt_typology');
    const filterFields = (typology) => {
        let optionFields = document.querySelectorAll('.plugin-option');

        optionFields.forEach((pluginOption) => {
            let inputs = pluginOption.querySelectorAll('input, select, textarea');
            if (pluginOption.classList.contains('type-' + typology)) {
                pluginOption.style.display = 'block';
                inputs.forEach(input => {
                    if (!input.classList.contains('notRequired')) {
                        input.setAttribute('required', '');
                    }
                });
            } else {
                pluginOption.style.display = 'none';
                inputs.forEach(input => input.removeAttribute('required'));
            }
        });
    };

    filterFields(typologyField.value);

    typologyField.addEventListener('change', function (event) {
        let typology = event.target.value;
        filterFields(typology);
    });
});
