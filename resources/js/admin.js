document.addEventListener('DOMContentLoaded', function() {

    const typologyField = document.getElementById('aat_typology');
    const azureLanguageField = document.getElementById('aat-language-azure-translate-instance');
    const filterFields = (typology, language) => {
        let optionFields = document.querySelectorAll('.plugin-option');

        optionFields.forEach((pluginOption) => {
            let inputs = pluginOption.querySelectorAll('input, select, textarea');
            if (pluginOption.classList.contains('type-' + typology)) {
                if (typology !== 'azure' || language !== 'en' || !pluginOption.classList.contains('not-default-language')) {
                    pluginOption.style.display = 'block';
                    inputs.forEach(input => input.setAttribute('required', ''));
                } else {
                    pluginOption.style.display = 'none';
                    inputs.forEach(input => input.removeAttribute('required'));
                }
            } else {
                pluginOption.style.display = 'none';
                inputs.forEach(input => input.removeAttribute('required'));
            }
        });
    };

    filterFields(typologyField.value, azureLanguageField.value);

    typologyField.addEventListener('change', function (event) {
        let typology = event.target.value;
        filterFields(typology, azureLanguageField.value);
    });

    azureLanguageField.addEventListener('change', function (event) {
        let language = event.target.value;
        filterFields(typologyField.value, language);
    });

});
