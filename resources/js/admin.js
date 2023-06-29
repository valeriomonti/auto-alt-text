const typologyField = document.getElementById('aat_typology');

const filterFields = (typology) => {
    let optionFields = document.querySelectorAll('.plugin-option');

    optionFields.forEach((pluginOption) => {
        if ( pluginOption.classList.contains( 'type-' + typology)) {
            pluginOption.style.display = 'table-row';
        } else {
            pluginOption.style.display = 'none';
        }
    });
}

filterFields(typologyField.value);

typologyField.addEventListener('change',function (event){
    let typology = event.target.value;
    filterFields(typology);
});