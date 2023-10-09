const typologyField = document.getElementById('aat_typology');
const azureLanguageField = document.getElementById('aat-language-azure-translate-instance');

const filterFields = (typology, language) => {
    let optionFields = document.querySelectorAll('.plugin-option');

    //const azureLanguageField = document.getElementById('aat-language-azure-translate-instance');

    optionFields.forEach((pluginOption) => {
        if ( pluginOption.classList.contains( 'type-' + typology) ) {
            if ( typology !== 'azure' || language !== 'en' || !pluginOption.classList.contains('not-default-language') ) {
                pluginOption.style.display = 'block';
            } else {
                pluginOption.style.display = 'none';
            }
            //pluginOption.style.display = 'table-row';
        } else {
            pluginOption.style.display = 'none';
        }
    });
}

filterFields(typologyField.value, azureLanguageField.value);

typologyField.addEventListener('change',function (event){
    let typology = event.target.value;
    filterFields(typology, azureLanguageField.value);
});

azureLanguageField.addEventListener('change',function (event){
    let language = event.target.value;
    filterFields(typologyField.value, language);
});