var question = 1;

$(document).ready(function () {

    function init(){
        handleNewField();
        handleSetJson();
        loadData();
        handleUI();
        handleDeleteModal();
    }

    function isReadOnly() {
        return $('#is-readonly').val() === '1';
    }

    function loadData(){
        let $formId = $('#form-id')[0].value;
        if($formId != 0){
            let $jsonStr = $('#json')[0].value;
            if ($jsonStr) {
                let $json = JSON.parse($jsonStr);
                let $formData = $json.formData;
                let questionId = 0;
                for(let data in $formData){
                    questionId = parseInt(data.match(/\d+/)[0]);
                    generateField(questionId, $formData[data]);
                }
                $('#form-name')[0].value = $json.formName;
                $('#form-type-dropdown')[0].value = $json.formType;
                question = questionId + 1;
            }
        }
    }

    function handleSetJson(){
        let $json = $('#json');
        let $saveButton = $('#save-button');
        let $save = $('#save');

        $saveButton.on('click', function (){
            let statementInput = $('.statement-input');
            let length = statementInput.length;
            let isEmpty = false;
            for(let i = 0; i < length; i++){
                let currEl = statementInput[i];
                if(currEl.value == ""){
                    isEmpty = true;
                    break;
                }
            }

            let formName = $('#form-name');
            if(formName[0].value == ""){
                isEmpty = true;
            }

            if(!isEmpty){
                let $fieldBox = $('.field-box');
                let fieldLength = $fieldBox.length;

                let $formTypeDropdown = $('#form-type-dropdown :selected')[0].value;
                let $formType = $('#form-type');
                $formType[0].value = $formTypeDropdown;

                let jsonTemp = {};
                for(let i = 0; i < fieldLength; i++){
                    let id = $fieldBox[i].id.split('-')[1];
                    let questionId = "question" + id;
                    let statement = $("#statement-" + id)[0].value;
                    let type = $('#answer-' + id)[0].className.split("-")[0];
                    let option = [];

                    if(type === "dropdown" || type === "multiple" || type === "checklist"){
                        let container = $('#' + type + "-container-" + id + " .options");
                        let containerLength = container.length;
                        for(let i = 0; i < containerLength; i++){
                            option.push(container[i].value);
                        }
                    }

                    let isRequired = $("#required-" + id)[0].checked;
                    let isMultiple = false;
                    if(type === 'photo'){
                        isMultiple = $("#multiple-" + id)[0].checked;
                    }

                    jsonTemp[questionId] = {
                        "question": statement,
                        "type": type,
                        "option": option,
                        "required": isRequired
                    };
                    if(type === 'photo'){
                        jsonTemp[questionId].multiple = isMultiple;
                    }
                }
                let jsonStructure = JSON.stringify(jsonTemp);
                $json[0].value = jsonStructure;

                if ($('#is_public').is(':checked')) {
                    if (!$('input[name="is_public"]').length) {
                        $('<input>').attr({
                            type: 'hidden',
                            name: 'is_public',
                            value: 'on'
                        }).appendTo('form');
                    }
                }

                $save.click();
            }else{
                alert('Please make sure all component are filled!');
            }

        })
    }

    function handleNewField() {
        let $button = $('#add-new-field');
        if ($button) {
            $button.on('click', function (e) {
                e.preventDefault();
                generateField(question, {});
                question++;
            });
        }
    }

    function generateField(question, data) {
        let field = generateStringField(question, data);
        $('#container-field').append(field);

        if(!$.isEmptyObject(data)){
            if(data.type === 'multiple' || data.type === 'checklist' || data.type === 'dropdown'){
                if (!isReadOnly()) {
                    addEventAdd(question, data.type);
                }
                let answerContainer = $('#answer-' + question + " .option-answer-container input");
                let answerContainerLength = answerContainer.length;
                for(let i = 0; i < answerContainerLength; i++){
                    let id = answerContainer[i].id;
                    if (!isReadOnly()) {
                        addEventDeleteOptionAnswer(id);
                    }
                }
            }
        }

        if (!isReadOnly()) {
            addEventTypeDropdown(question);
            addEventDeleteQuestion(question);
        }
    }

    function generateStringField(question, data) {
        const readOnly = isReadOnly();
        const readonlyAttr = readOnly ? "readonly" : "";
        const disabledAttr = readOnly ? "disabled" : "";

        if ($.isEmptyObject(data)) {
            return `
                <div class="container-field" id="container-field-${question}">
                    <div class="field-box" id="question-${question}" style="${readOnly ? 'width: 100%; border-radius: 7px;' : ''}">
                            <div class="top-field">
                                <div class="statement-type">
                                    <div class="statement">
                                        <div class="statement-title">Statement/Question</div>
                                        <div>
                                            <input type="text" class="statement-input" id="statement-${question}" placeholder="Please input your question or statement here" required>
                                        </div>
                                    </div>

                                    <div class="type-answer">
                                        <div class="answer-title">Type of Answer</div>
                                        <div class="answer-option">
                                            <select id="type-${question}" class="title-field-input-dropdown" ${disabledAttr}>
                                                <option value="text" selected>Text</option>
                                                <option value="multiple">Multiple Choice</option>
                                                <option value="checklist">Checklist</option>
                                                <option value="longtext">Long Text</option>
                                                <option value="date">Date</option>
                                                <option value="time">Time</option>
                                                <option value="datetime">Date Time</option>
                                                <option value="dropdown">Dropdown</option>
                                                <option value="photo">Photo</option>
                                            </select>
                                            <div class="photo-options" id="photo-options-${question}" style="display:none; margin-top:8px;">
                                                <label>
                                                    <input type="checkbox" id="multiple-${question}" ${disabledAttr} /> Allow multiple images
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="answer-${question}" class="text-field bot-field answer-input">
                                    <input type="text" class="answer-input-text" placeholder="The answer will be here . . ." disabled />
                                </div>

                                <label class="toggle">
                                    <span class="toggle-label">Required</span>
                                    <input id="required-${question}" class="toggle-checkbox" type="checkbox" checked ${disabledAttr}>
                                    <div class="toggle-switch"></div>
                                </label>

                            </div>
                        </div>
                        ${readOnly ? '' : `
                        <div class="delete-button" id="delete-${question}">
                            <button class="delete-field"><i class='fa-solid fa-trash-can fa-2x' style='color:#ffffff'></i></button>
                        </div>
                        `}
                    </div>

            `
        } else {
            let html = `
                <div class="container-field" id="container-field-${question}">
                    <div class="field-box" id="question-${question}" style="${readOnly ? 'width: 100%; border-radius: 7px;' : ''}">
                        <div class="top-field">
                            <div class="statement-type">
                                <div class="statement">
                                    <div class="statement-title">Statement/Question</div>
                                    <div>
                                        <input type="text" class="statement-input" id="statement-${question}" placeholder="Please input your question or statement here" value="${data.question}" required ${readonlyAttr}>
                                    </div>
                                </div>

                                <div class="type-answer">
                                    <div class="answer-title">Type of Answer</div>
                                    <div class="answer-option">
                                        <select id="type-${question}" class="title-field-input-dropdown" ${disabledAttr}>
                                            <option value="text" ${data.type === "text" ? "selected" : ""}>Text</option>
                                            <option value="multiple" ${data.type === "multiple" ? "selected" : ""}>Multiple Choice</option>
                                            <option value="checklist" ${data.type === "checklist" ? "selected" : ""}>Checklist</option>
                                            <option value="longtext" ${data.type === "longtext" ? "selected" : ""}>Long Text</option>
                                            <option value="date" ${data.type === "date" ? "selected" : ""}>Date</option>
                                            <option value="time" ${data.type === "time" ? "selected" : ""}>Time</option>
                                            <option value="datetime" ${data.type === "datetime" ? "selected" : ""}>Date Time</option>
                                            <option value="dropdown" ${data.type === "dropdown" ? "selected" : ""}>Dropdown</option>
                                            <option value="photo" ${data.type === "photo" ? "selected" : ""}>Photo</option>
                                        </select>
                                        <div class="photo-options" id="photo-options-${question}" style="display:${data.type==='photo' ? 'block' : 'none'}; margin-top:8px;">
                                            <label>
                                                <input type="checkbox" id="multiple-${question}" ${data.multiple ? 'checked' : ''} ${disabledAttr} /> Allow multiple images
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
            `;
            // for edit
            if (data.type === 'text') {
                html = html + `
                            <div id="answer-${question}" class="text-field bot-field">
                                <input type="text" class="answer-input-text" placeholder="The answer will be here . . ." disabled />
                            </div>
                `;
            } else if (data.type === 'multiple' || data.type === 'checklist') {
                let inputType = data.type === 'multiple' ? 'radio' : 'checkbox';
                html = html + `
                            <div id="answer-${question}" class="${data.type}-field">
                                <div id="${data.type}-container-${question}">`
                $.each(data.option, function (_, opt){
                    let lowOpt = opt.toLowerCase();
                    let newOptVal = $.trim(lowOpt.replaceAll(/\s+/g, ' '));
                    let newId = newOptVal.replaceAll(' ', '-') + "-" + question;
                    html = html + `
                                    <div id="${newId}-answer" class="option-answer-container">
                                        <div class="left-option">
                                            <input class="options" type="${inputType}" id="${newId}" name="${data.type}-${question}" value="${opt}" disabled>
                                            <label class="options-label" for="${newId}">${opt}</label><br>
                                        </div>
                                        ${readOnly ? '' : `
                                        <div class="right-option">
                                            <button class="delete-answer" id="${newId}-delete"><i class="fa-solid fa-circle-xmark fa-lg" style="color:#ff0000c7"></i></button>
                                        </div>
                                        `}
                                    </div>
                    `
                });
                html = html + `
                                </div>
                                ${readOnly ? '' : `
                                <div class="option-field">
                                    <input class="${data.type}-input" type="text" name="new-option-${question}" id="new-option-${question}">
                                    <button class="add-option-button" id="add-${data.type}-${question}">Add Option</button>
                                </div>
                                `}
                            </div>
                `;
            } else if (data.type === 'longtext') {
                html = html + `
                            <div id="answer-${question}" class="longtext-field bot-field">
                                <textarea type="text" class="answer-input-area" placeholder="The answer will be here . . ." disabled></textarea>
                            </div>
                `;
            } else if (data.type === 'date') {
                html = html + `
                            <div id="answer-${question}" class="date-field bot-field">
                                <input class="date-input" type="date" disabled>
                            </div>
                `;
            } else if (data.type === 'time') {
                html = html + `
                            <div id="answer-${question}" class="time-field bot-field">
                                <input class="time-input" type="time" disabled>
                            </div>
                `;
            } else if (data.type === 'datetime') {
                html = html + `
                            <div id="answer-${question}" class="datetime-field bot-field">
                                <input class="date-time-input" type="datetime-local" disabled>
                            </div>
                `;
            } else if (data.type === 'dropdown') {
                html = html + `
                            <div id="answer-${question}" class="dropdown-field">
                                <div id="dropdown-container-${question}">`;
                $.each(data.option, function (_, opt) {
                    let lowOpt = opt.toLowerCase();
                    let newOptVal = $.trim(lowOpt.replaceAll(/\s+/g, ' '));
                    let newId = newOptVal.replaceAll(' ', '-') + "-" + question;
                    html = html + `
                                <div id="${newId}-answer" class="option-answer-container">
                                    <div class="left-option">
                                        <input type="hidden" class="options" id="${newId}" value="${opt}">
                                        <span class="options-label" style="display: block; margin-bottom: 5px;">${opt}</span>
                                    </div>
                                    ${readOnly ? '' : `
                                    <div class="right-option">
                                        <button class="delete-answer" id="${newId}-delete"><i class="fa-solid fa-circle-xmark fa-lg" style="color:#ff0000c7"></i></button>
                                    </div>
                                    `}
                                </div>
                    `;
                });
                html = html + `
                                </div>
                                ${readOnly ? '' : `
                                <div class="option-field">
                                    <input class="dropdown-input" type="text" name="new-option-${question}" id="new-option-${question}">
                                    <button class="add-value-button" id="add-dropdown-${question}">Add Value</button>
                                </div>
                                `}
                            </div>
                `;
            } else if (data.type === 'photo') {
                html = html + `
                            <div id="answer-${question}" class="photo-field bot-field">
                                <input type="file" accept="image/*" disabled />
                            </div>
                `;
            }

            html = html + `
                                <label class="toggle">
                                    <span class="toggle-label">Required</span>
                                    <input id="required-${question}" class="toggle-checkbox" type="checkbox" ${data.required ? "checked" : ""} ${disabledAttr}>
                                    <div class="toggle-switch"></div>
                                </label>

                            </div>
                        </div>
                        ${readOnly ? '' : `
                        <div class="delete-button" id="delete-${question}">
                            <button class="delete-field"><i class='fa-solid fa-trash-can fa-2x' style='color:#ffffff'></i></button>
                        </div>
                        `}
                    </div>
                </div>
            `
            return html;
        }
    }

    function addEventTypeDropdown(question) {
        let questionId = '#type-' + question;
        let answerId = '#answer-' + question;
        let $typeDropdown = getElement(questionId);
        if ($typeDropdown) {
            $typeDropdown.on('change', function (e) {
                e.preventDefault();
                let val = getValue(questionId);
                let answerField = getElement(answerId);
                if (answerField[0]) {
                    if (!answerField[0].classList.contains(val + '-field')) {
                        let newField = "";
                        let addEvent = false;
                        if (val === 'text') {
                            newField = `
                                <div id="answer-${question}" class="text-field bot-field">
                                    <input type="text" class="answer-input-text" placeholder="The answer will be here . . ." disabled />
                                </div>
                            `;
                            $('#photo-options-'+question).hide();
                        } else if (val === 'photo') {
                            newField = `
                                <div id="answer-${question}" class="photo-field bot-field">
                                    <input type="file" accept="image/*" disabled />
                                </div>
                            `;
                            $('#photo-options-'+question).show();
                        } else if (val === 'multiple') {
                            newField = `
                                <div id="answer-${question}" class="multiple-field">
                                    <div id="multiple-container-${question}"></div>
                                    <div class="option-field">
                                        <input class="multiple-input" type="text" name="new-option-${question}" id="new-option-${question}">
                                        <button class="add-option-button" id="add-multiple-${question}">Add Option</button>
                                    </div>
                                </div>
                            `;
                            addEvent = true;
                            $('#photo-options-'+question).hide();
                        } else if (val === 'checklist') {
                            newField = `
                                <div id="answer-${question}" class="checklist-field">
                                    <div id="checklist-container-${question}"></div>
                                    <div class="option-field">
                                        <input class="checklist-input" type="text" name="new-option-${question}" id="new-option-${question}">
                                        <button class="add-option-button" id="add-checklist-${question}">Add Option</button>
                                    </div>
                                </div>
                            `;
                            addEvent = true;
                            $('#photo-options-'+question).hide();
                        } else if (val === 'longtext') {
                            newField = `
                                <div id="answer-${question}" class="longtext-field bot-field">
                                    <textarea type="text" class="answer-input-area" placeholder="The answer will be here . . ." disabled></textarea>
                                </div>
                            `;
                            $('#photo-options-'+question).hide();
                        } else if (val === 'date') {
                            newField = `
                                <div id="answer-${question}" class="date-field bot-field">
                                    <input class="date-input" type="date" disabled>
                                </div>
                            `;
                            $('#photo-options-'+question).hide();
                        } else if (val === 'time') {
                            newField = `
                                <div id="answer-${question}" class="time-field bot-field">
                                    <input class="time-input" type="time" disabled>
                                </div>
                            `;
                            $('#photo-options-'+question).hide();
                        } else if (val === 'datetime') {
                            newField = `
                                <div id="answer-${question}" class="datetime-field bot-field">
                                    <input class="date-time-input" type="datetime-local" disabled>
                                </div>
                            `;
                            $('#photo-options-'+question).hide();
                        } else if (val === 'dropdown') {
                            newField = `
                                <div id="answer-${question}" class="dropdown-field">
                                    <div id="dropdown-container-${question}"></div>
                                    <div class="option-field">
                                        <input class="dropdown-input" type="text" name="new-option-${question}" id="new-option-${question}">
                                        <button class="add-value-button" id="add-dropdown-${question}">Add Value</button>
                                    </div>
                                </div>
                            `;
                            addEvent = true;
                            $('#photo-options-'+question).hide();
                        }
                        answerField.replaceWith(newField);
                        if(addEvent){
                            addEventAdd(question, val);
                        }
                    }
                }
            });
        }
    }

    function addEventDeleteQuestion(question) {
        let deleteId = "#delete-" + question;
        let containerId = "#container-field-" + question;
        let $delete = getElement(deleteId);
        if ($delete) {
            $delete.on('click', function (e) {
                e.preventDefault();
                getElement(containerId).remove();
            })
        }
    }

    function addEventAdd(question, val) {
        let containerId = '#' + val + '-container-' + question;
        let buttonId = '#add-' + val + '-' + question;
        let newOptionId = '#new-option-' + question;

        let $addButton = getElement(buttonId);
        if ($addButton) {
            $addButton.on('click', function (e) {
                e.preventDefault();
                let newOptVal = getValue(newOptionId);
                if (newOptVal) {
                    newOptVal = $.trim(newOptVal.replaceAll(/\s+/g, ' '));
                    let newId = newOptVal.replaceAll(' ', '-') + "-" + question
                    let newField = '';
                    if(val === "multiple" || val === "checklist"){
                        let type = val === "multiple" ? "radio" : "checkbox";
                        newField = `
                                <div class="option-answer-container" id="${newId}-answer">
                                    <div class="left-option">
                                        <input class="options" type="${type}" id="${newId}" name="${val}-${question}" value="${newOptVal}" disabled>
                                        <label class="options-label" for="${newId}">${newOptVal}</label><br>
                                    </div>
                                    <div class="right-option">
                                        <button class="delete-answer" id="${newId}-delete"><i class="fa-solid fa-circle-xmark fa-lg" style="color:#ff0000c7"></i></button>
                                    </div>
                                </div>
                            `;
                    }else if(val === "dropdown"){
                        newField = `
                                <div class="option-answer-container" id="${newId}-answer">
                                    <div class="left-option">
                                        <input type="hidden" class="options" id="${newId}" value="${newOptVal}">
                                        <span class="options-label" style="display: block; margin-bottom: 5px;">${newOptVal}</span>
                                    </div>
                                    <div class="right-option">
                                        <button class="delete-answer" id="${newId}-delete"><i class="fa-solid fa-circle-xmark fa-lg" style="color:#ff0000c7"></i></button>
                                    </div>
                                </div>
                            `;
                    }
                    let $container = getElement(containerId);
                    if ($container) {
                        $container.append(newField);
                        getElement(newOptionId)[0].value = null;
                        addEventDeleteOptionAnswer(newId);
                    }

                }
            });
        }
    }

    function addEventDeleteOptionAnswer(newId){
        let $deleteButton = $('#' + newId + "-delete");
        if($deleteButton){
            $deleteButton.on('click', function (e){
                e.preventDefault();
                getElement('#' + newId + "-answer").remove();
            })
        }
    }

    function handleUI(){
        let $container = $('#container');
        let topBarHeight = $('#top-bar').height();
        let winHeight = window.screen.height;
        let height = winHeight - topBarHeight;
        $container.height(height);
    }

    function handleDeleteModal() {
        $('#open-delete-modal').on('click', function() {
            $('#delete-modal').show();
        });

        $('#close-delete-modal').on('click', function() {
            $('#delete-modal').hide();
        });

        // Close modal when clicking outside of the confirmation container
        $('#delete-modal').on('click', function(e) {
            if (e.target.id === 'delete-modal') {
                $(this).hide();
            }
        });
    }

    if ($('#json').val()) {
        try {
            var formData = JSON.parse($('#json').val());
            if (formData.is_public) {
                $('#is_public').prop('checked', true);
            }
        } catch(e) {}
    }

    init();

});

function getValue(questionId) {
    return $(questionId)[0].value ? $(questionId)[0].value : null;
}

function getElement(element) {
    return $(element);
}

function getStringId(string) {
    return '#' + string;
}

function changeUse(e, type, currEl) {
    e.preventDefault();
    currType = type;
    $(getStringId($('.selected-type')[0].id)).removeClass('selected-type');
    $(getStringId(currEl.id)).addClass('selected-type');
    showHideByType();
}

function showHideByType() {
    let otherType = typeList.filter((type) => type != currType);
    $.each(otherType, function (_, type) {
        let otherClassList = $('.' + type);
        $.each(otherClassList, function (_, currClass) {
            let $el = getElement('#' + currClass.id);
            $el.hide();
        });
    })
    let classList = $('.' + currType);
    $.each(classList, function (_, currClass) {
        let $el = getElement('#' + currClass.id);
        $el.show();
    });
}