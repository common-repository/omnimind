(function ($) {

    var activeTabIndex = localStorage.getItem('activeTabIndex');

    if (activeTabIndex === null) {
        activeTabIndex = 0;
    }

    $('.tab-opener').eq(activeTabIndex).addClass('active');
    $('.tab-item').eq(activeTabIndex).addClass('active');

    $('.tab-opener').click(function (event) {
        event.preventDefault();
        if (!$(this).hasClass('active')) {
            var aim = $(this).parents('.tab-control').find('.tab-opener').removeClass('active').index(this);
            $(this).addClass('active');
            $(this).parents('.tabset').find('.tab-item').removeClass('active').eq(aim).addClass('active');

            localStorage.setItem('activeTabIndex', aim);
        }
    });

    $('.advanced-settings__opener').click(function (e) {
        let item = $(this).closest('.advanced-settings');
        item.find('.advanced-settings__content').slideToggle(function () {
            item.toggleClass('active');
        });
        e.preventDefault();

    });

    function preserveOrderOnSelect2Choice(e) {
        var id = e.params.data.id;
        var option = $(e.target).children('[value=' + id + ']');
        option.detach();
        $(e.target).append(option).change();
    }

    po_select2s = $('.js-example-basic-multiple').select2()
    po_select2s.each(function () {
        $(this).on('select2:select', preserveOrderOnSelect2Choice);
    });

})(jQuery);


// checkbox expand post type
var checkboxes = document.querySelectorAll('.content-type-head input[type="checkbox"]');

function handleCheckboxChange(event) {
    var parentItem = event.target.closest('.content-types__item');
    if (!parentItem) {
        return;
    }
    var attributesWrap = parentItem.querySelector('.attributes-wrap');
    if (event.target.checked) {
        attributesWrap.style.display = 'block';
    } else {
        attributesWrap.style.display = 'none';
    }
}

checkboxes.forEach(function (checkbox) {
    checkbox.addEventListener('change', handleCheckboxChange);
});
checkboxes.forEach(function (checkbox) {
    handleCheckboxChange({target: checkbox});
});
// add new field
document.addEventListener('DOMContentLoaded', function () {

    const dropdown = document.querySelector("#existing_project");
    const settingsLink = document.querySelector('.btn-omni--primary[href]');
    const selectButton = document.querySelector('button[name="select_project"]');
    //settingsLink.style.display = "none";
    dropdown.addEventListener('change', () => {
        const selectedOption = dropdown.options[dropdown.selectedIndex];
        if (selectedOption.hasAttribute('selected')) {
            settingsLink.style.display = 'inline-flex';
            selectButton.style.display = 'none';
        } else {
            settingsLink.style.display = 'none';
            selectButton.style.display = 'inline-flex';
        }
    });

    const modal = document.getElementById('omni_modal');
    const openModalButton = document.getElementById('openModal');
    const submitProjectButton = document.getElementById('submitProject');
    const projectNameInput = document.getElementById('projectName');
    const closeModalButton = document.querySelector('.omni-modal-close');

    openModalButton.addEventListener('click', function (e) {
        e.preventDefault();
        openModal();
    });
    submitProjectButton.addEventListener('click', submitProject);
    closeModalButton.addEventListener('click', closeModal);
    window.addEventListener('click', closeIfClickedOutside);

    function openModal() {
        modal.style.display = 'block';
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    function submitProject() {
        const projectName = projectNameInput.value;
        if (projectName.length > 0) {
            createProject(projectName);
            projectNameInput.value = '';
            closeModal();
        }
    }

    function closeIfClickedOutside(event) {
        if (event.target == modal) {
            closeModal();
        }
    }

    function createProject(projectName) {
        projectName = projectName.trim(); // Remove unnecessary white spaces
        if (projectName === '') {
            alert('Project name should not be empty!');
            return;
        }

        const params = new URLSearchParams();
        const nonce = document.getElementById('project_create_nonce').value;

        params.append('action', 'create_project_action');
        params.append('projectName', projectName);
        params.append('nonce', nonce);

        fetch(ajaxurl, {
            method: 'POST',
            body: params
        })
            .then(response => response.json())
            .then(data =>  {
                omniAlertHandler(data.success ? 'success' : 'warning', data.data );

                setTimeout(() => {
                    location.reload();
                }, 3000);
            })
            .catch((error) => {
                console.error('Error:', error);
            });
    }

    document.querySelectorAll('.add-field').forEach(button => {
        button.addEventListener('click', function () {
            var tableName = this.getAttribute('data-post-type');
            var fieldNameInput = this.parentNode.querySelector('.new-field-name');
            var fieldName = fieldNameInput.value;

            if (fieldName.trim() !== '') {
                var formattedFieldName = formatFieldName(fieldName);

                var parentItem = this.closest('.content-types__item');
                var table = parentItem.querySelector('.attributes-table');
                var newRow = table.insertRow();

                newRow.innerHTML = `
				<td>${fieldName}</td>
				<td>
				<input type="hidden" name="post_type_fields[${tableName}][${fieldName}][name]" value="${fieldName}">
				<input type="checkbox" name="post_type_fields[${tableName}][${fieldName}][status]" value="1" class="checkbox">
				</td>
				<td>
				<input type="text" class="form-input" name="post_type_fields[${tableName}][${fieldName}][label]" value="${formattedFieldName}">
				</td>
				`;
            }
            fieldNameInput.value = '';
        });
    });
});

function formatFieldName(fieldName) {
    if (fieldName[0] === '_') {
        fieldName = fieldName.substring(1);
    }

    return fieldName.replace(/^[a-z]/, function (match) {
        return match.toUpperCase();
    }).replace(/_/g, ' ');
}

// autocomplete
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.new-field-name').forEach(input => {
        input.addEventListener('input', function () {
            var postType = this.getAttribute('data-post-type');
            var autocompleteData = JSON.parse(document.querySelector('.autocomplete-data[data-post-type="' + postType + '"]').textContent);
            var value = this.value.toLowerCase();

            closeAllLists(input);

            if (!value) return false;
            var list = document.createElement("DIV");
            list.setAttribute("class", "autocomplete-items");
            this.parentNode.appendChild(list);

            autocompleteData.forEach(function (item) {
                if (item.toLowerCase().includes(value)) {
                    var itemDiv = document.createElement("DIV");
                    itemDiv.setAttribute("class", "autocomplete-items__item");
                    itemDiv.innerHTML = item;
                    itemDiv.innerHTML += "<input type='hidden' value='" + item + "'>";
                    itemDiv.addEventListener("click", function () {
                        input.value = this.getElementsByTagName("input")[0].value;
                        closeAllLists(input);
                    });
                    list.appendChild(itemDiv);
                }
            });
        });
    });

    function closeAllLists(el, inputElement) {
        var items = document.getElementsByClassName("autocomplete-items");
        for (var i = 0; i < items.length; i++) {
            if (el != items[i] && el != inputElement) {
                items[i].parentNode.removeChild(items[i]);
            }
        }
    }

    document.addEventListener("click", function (e) {
        closeAllLists(e.target);
    });
});

const syncForm = document.getElementById('syncForm');
if (syncForm) {

    // Alert Modal
    const omniAlertModal = document.getElementById('omniAlertModal');

    // Alert Close Btn
    const closeButton = omniAlertModal.querySelector('.omni-modal__close');
    closeButton.addEventListener('click', function () {
        omniAlertModal.classList.remove('omni-modal--show');
    });

    const progressBarWrp = document.querySelector('.progress-bar__wrap');
    const progressBar = progressBarWrp.querySelector('#progress-bar');
    let totalTime, startTime;

    function submitForm(event, pointer = -1) {
        if (event) event.preventDefault();
        pointer = localStorage.getItem('pointer') || pointer;
        const requestStartTime = Date.now();
        const nonce = document.getElementById('project_sync_nonce').value;

        let formData = new FormData();

        formData.append('action', 'sync_data_action');
        formData.append('pointer', pointer);
        formData.append('nonce', nonce);

        progressBarWrp.classList.remove('omni-progress--hide');

        // Disable Button && add loader class
        let syncFormSubmitButton = event ? event.submitter : document.querySelector('#sync-button');
        syncFormSubmitButton.disabled = true;
        syncFormSubmitButton.classList.add('btn-omni--loading');

        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json()
                .then(data => ({data, requestTime: Date.now() - requestStartTime})))
            .then(({data: res, requestTime}) => {
                const delay = 200 + requestTime;
                //const fi = 2;
                totalTime = res.data.count * delay / 1000; // total time in seconds including the request time
                startTime = Date.now();

                startCountdown();

                progressBar.value = parseInt(res.data.pointer);
                localStorage.setItem('pointer', res.data.pointer);
                progressBar.setAttribute('max', res.data.count);

                if (res.data.pointer > -1) {
                    setTimeout(function () {
                        submitForm(null, res.data.pointer);
                    }, 200);
                } else {
                    processCompleted(res.data, syncFormSubmitButton);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }

    function processCompleted(data, syncFormSubmitButton) {
        localStorage.removeItem('pointer');
        progressBar.value = data.count;
        progressBarWrp.classList.add('omni-progress--hide');
        document.querySelector('#progress-bar__res').innerText = data.count === 0
            ? 'No new content to sync.'
            : 'In total ' + data.count + ' posts synced successfully!'
        syncFormSubmitButton.disabled = false;
        syncFormSubmitButton.classList.remove('btn-omni--loading');
    }

    function startCountdown() {
        // Update the countdown every second
        const countdownInterval = setInterval(() => {
            const elapsedTime = (Date.now() - startTime) / 1000; // elapsed time in seconds
            const remainingTime = totalTime - elapsedTime; // remaining time in seconds

            if (remainingTime <= 0) {
                clearInterval(countdownInterval);
            } else {
                document.querySelector('#remaining_time').innerHTML = formatTime(remainingTime);
            }
        }, 1000);
    }

    function formatTime(seconds) {
        // Convert seconds into minutes and seconds in format mm:ss
        const minutes = Math.floor(seconds / 60);
        seconds = Math.floor(seconds % 60);

        return `${minutes.toString().padStart(2, "0")}:${seconds.toString().padStart(2, "0")}`;
    }

    syncForm.addEventListener('submit', function (e) {
        e.preventDefault();
        submitForm(e, -1);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const alertTrigger = document.getElementById('omni_alert');
    if (alertTrigger && alertTrigger.hasAttribute('data-status') && alertTrigger.hasAttribute('data-message')) {
        omniAlertHandler(alertTrigger.getAttribute('data-status'), alertTrigger.getAttribute('data-message'));
    }
});

/**
 * Omnimind Settings
 * Dashboard Alert Handler
 */
function omniAlertHandler(alertStatus, alertMessage) {

    // Alert Modal
    const omniAlertModal = document.getElementById('omniAlertModal');

    // Alert Modal Close Btn
    const closeButton = omniAlertModal.querySelector('.omni-modal__close');
    closeButton.addEventListener('click', function () {
        omniAlertModal.classList.remove('omni-modal--show');
    });

    // Alert Modal Message container
    const omniModalText = omniAlertModal.querySelector('.omni-modal__text');

    // Show Alert Modal
    omniAlertModal.classList.add('omni-modal--show');

    // Handle Success
    if (alertStatus === "success") {
        omniAlertModal.classList.add('omni-modal--success');
        omniModalText.innerHTML = alertMessage;
    }

    // Handle Warning
    if (alertStatus === "warning") {
        omniAlertModal.classList.add('omni-modal--warning');
        omniModalText.innerHTML = alertMessage;
    }

    // Hide modal after 5 seconds
    setTimeout(function () {
        omniAlertModal.classList.remove('omni-modal--show');
    }, 5000);
}


/**
 * Load functions on page loaded
 */
document.addEventListener('DOMContentLoaded', function () {
    omniAlertHandler();
});

new DataTable('#request_table', {
    columnDefs: [
        {
            targets: 0,
            render: DataTable.render.datetime('d/m/Y H:i'),
            orderable: true,
        },
        {
            targets: '_all',
            orderable: false,  // disable ordering for all other columns
        }
    ],
    order: [[0, 'desc']]
});

document.addEventListener('DOMContentLoaded', function() {
    const maxLength = 100;

    function truncateContent(content, maxLength) {
        let div = document.createElement('div');
        div.innerHTML = content;
        let totalLength = 0;
        let truncateNode;
        let stack = [...div.childNodes];

        while (stack.length) {
            let node = stack.shift();
            if (node.nodeType === Node.TEXT_NODE) {
                totalLength += node.textContent.length;
                if (totalLength > maxLength) {
                    let overLength = totalLength - maxLength;
                    node.textContent = node.textContent.substring(0, node.textContent.length - overLength) + '...';
                    truncateNode = node;
                    break;
                }
            } else {
                stack = [...node.childNodes, ...stack];
            }
        }

        while (stack.length) {
            let node = stack.pop();
            if (node !== truncateNode) {
                node.parentNode.removeChild(node);
            }
        }

        return div.innerHTML;
    }

    const dataAnswerTds = document.querySelectorAll('.data-answer');

    dataAnswerTds.forEach(function(td) {
        const originalContent = td.innerHTML;

        if (originalContent.length > maxLength) {
            const truncatedContent = truncateContent(originalContent, maxLength);
            td.dataset.originalContent = originalContent; // Store original content
            td.innerHTML = `
                <span class="shortened">${truncatedContent}</span>
                <span class="full-content" style="display: none;">${originalContent}</span>
                <button class="toggle-button btn-omni btn-omni--primary">Show More</button>
            `;
        }
    });

    document.querySelectorAll('.toggle-button').forEach(function(button) {
        button.addEventListener('click', function() {
            const td = this.parentNode;
            const shortened = td.querySelector('.shortened');
            const fullContent = td.querySelector('.full-content');

            if (shortened.style.display === 'none') {
                shortened.style.display = '';
                fullContent.style.display = 'none';
                this.textContent = 'Show More';
            } else {
                shortened.style.display = 'none';
                fullContent.style.display = '';
                this.textContent = 'Show Less';
            }
        });
    });
});