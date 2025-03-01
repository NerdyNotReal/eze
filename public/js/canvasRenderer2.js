/* GLOBAL Variables*/
let selectedElement = null;
const formCanvas = document.querySelector("#form-canvas");
const formCanvasMsg = document.querySelector(".form-canvas--message");

// Remove styling sidebar variables
// const stylingSidebar = document.querySelector("#styling-sidebar");
// const stylingCanontrols = document.querySelector("#styling-controls");

//Toggle canvas message
const toggleCanvasMsg = () => {
  formCanvasMsg.style.display =
    formCanvas.childElementCount > 1 ? "none" : "flex";
};

// Remove styling functions
// const enablestylingSidebar = (formQuestion) => { ... };
// const addStylingControls = (target) => { ... };

//Create new element with attributes and event listeners
const createElement = (
  type,
  attributes = {},
  textContent = "",
  events = {}
) => {
  const element = document.createElement(type);

  Object.keys(attributes).forEach((attr) => {
    element[attr] = attributes[attr];
  });
  element.textContent = textContent;
  Object.keys(events).forEach((event) => {
    element.addEventListener(event, events[event]);
  });
  return element;
};

//main structure to create forms
const createFormWrapper = (label) => {
  const canvasItem = createElement("div", { className: "canvas__item" });
  canvasItem.appendChild(
    createElement("p", { className: "canvas__item--type" }, label)
  );

  // Remove the "Customize" text since we removed styling functionality
  return canvasItem;
};

const createDeleteButton = () => {
  return createElement("button", { className: "btn btn__remove" }, "Remove", {
    click: async (event) => {
      event.preventDefault();
      const canvasItem = event.target.closest(".canvas__item");
      if (!canvasItem) return;

      const formId = new URLSearchParams(window.location.search).get('id');
      const elementId = canvasItem.dataset.elementId;

      try {
        if (formId && elementId) {
          const response = await fetch('../backend/api/delete_form_element.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({ formId, elementId })
          });

          const result = await response.text();
          let jsonResult;
          try {
            jsonResult = JSON.parse(result);
          } catch (e) {
            throw new Error('Invalid JSON response: ' + result);
          }

          if (!response.ok) {
            throw new Error(jsonResult.error || 'Failed to delete element');
          }
        }
        
        canvasItem.remove();
        toggleCanvasMsg();
      } catch (error) {
        console.error('Error deleting element:', error);
        alert('Failed to delete element: ' + error.message);
      }
    },
  });
};

const createFieldDescription = () => {
  const descriptionInput = createElement("input", {
    type: "text",
    placeholder: "Set Field description",
    className: "canvas__item--input text-body-medium description-input",
  });

  const previewDescription = createElement(
    "p",
    {
      className: "field-description text-body-small text-neutral-50",
      style: "margin-top: 4px",
    },
    "Form Description Goes Here!"
  );

  descriptionInput.addEventListener("input", () => {
    previewDescription.textContent = descriptionInput.value || "";
  });
  return {descriptionInput, previewDescription}
};

//Tool tip
const createToolTipOption = (previewInput) => {
  const tooltipInput = createElement("input", {
    type: "text",
    placeholder: "Set Tooltip Text",
    className: "canvas__item--input text-body-medium tooltip-input"
  });

  tooltipInput.addEventListener("input", () => {
    previewInput.title = tooltipInput.value || "";
  });

  return tooltipInput;
};

const createRequiredOption = () => {
  const requiredWrapper = createElement("div", {
    style: "display: flex; gap: 8px; align-items: center; margin: 10px 0; padding: 5px; background-color:rgb(21, 23, 26); border-radius: 4px;"
  });

  const requiredCheckbox = createElement("input", {
    type: "checkbox",
    className: "required-checkbox",
    style: "cursor: pointer;"
  });

  const requiredLabel = createElement("label", {
    className: "text-body-medium",
    style: "color: #333; font-weight: 500; cursor: pointer; user-select: none;"
  }, "Required");

  requiredWrapper.appendChild(requiredCheckbox);
  requiredWrapper.appendChild(requiredLabel);

  // Make the label clickable for the checkbox
  requiredLabel.addEventListener('click', () => {
    requiredCheckbox.checked = !requiredCheckbox.checked;
  });

  return { requiredWrapper, requiredCheckbox };
};

const createTextField = async (type = "text-field", savedData = null, elementId = null) => {
  const formWrapper = createFormWrapper(type.split("-").map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(" "));
  if (elementId) {
    formWrapper.dataset.elementId = elementId;
  }

  // Live Preview Section
  const formQuestion = createElement("div", {
    className: "text-body-medium text-neutral-50",
    style: "border: 1px dashed #ccc; padding: 10px; margin-bottom: 10px; width: 100%;",
  });

  const previewLabel = createElement("label", {
    style: "display: block; margin-bottom: 8px; color: #333;"
  }, savedData?.label || "Field Label");
  
  // Set input type based on field type
  let inputType = "text";
  let inputAttributes = {};
  
  switch(type) {
    case "email-field":
      inputType = "email";
      inputAttributes.pattern = "[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,}$";
      break;
    case "phone-field":
      inputType = "tel";
      inputAttributes.pattern = "[0-9]{10}";
      break;
    case "file-upload":
      inputType = "file";
      break;
    case "image-upload":
      inputType = "file";
      inputAttributes.accept = "image/*";
      break;
    case "document-upload":
      inputType = "file";
      inputAttributes.accept = ".pdf,.doc,.docx";
      break;
    case "url-field":
      inputType = "url";
      break;
    case "color-picker":
      inputType = "color";
      break;
    case "price-field":
      inputType = "number";
      inputAttributes.min = "0";
      inputAttributes.step = "0.01";
      break;
    case "calculation-field":
      inputType = "number";
      inputAttributes.readonly = true;
      break;
    case "signature-field":
      inputType = "text";
      inputAttributes.class = "signature-input";
      break;
    default:
      inputType = "text";
  }

  const previewInput = createElement("input", {
    type: inputType,
    className: `canvas__item--input text-body-medium ${inputAttributes.class || ''}`,
    placeholder: savedData?.placeholder || "Enter your answer",
    style: "width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;",
    ...inputAttributes
  });

  // Field Label Input
  const labelInput = createElement("input", {
    type: "text",
    placeholder: "Set Field Label",
    className: "canvas__item--input text-body-medium label-input",
    style: "width: 100%; margin-bottom: 8px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;",
    value: savedData?.label || ""
  });

  labelInput.addEventListener("input", () => {
    previewLabel.textContent = labelInput.value || "Field Label";
  });

  // Required Option
  const { requiredWrapper, requiredCheckbox } = createRequiredOption();
  requiredCheckbox.checked = savedData?.required || false;

  // Description
  const {descriptionInput, previewDescription} = createFieldDescription();
  if (savedData?.description) {
    descriptionInput.value = savedData.description;
    previewDescription.textContent = savedData.description;
  }

  // Tooltip
  const tooltipInput = createToolTipOption(previewInput);
  if (savedData?.tooltip) {
    tooltipInput.value = savedData.tooltip;
    previewInput.title = savedData.tooltip;
  }

  // Delete Button
  const deleteButton = createDeleteButton();

  // Append elements
  formQuestion.appendChild(previewLabel);
  formQuestion.appendChild(previewInput);
  formQuestion.appendChild(previewDescription);

  formWrapper.appendChild(labelInput);
  formWrapper.appendChild(descriptionInput);
  formWrapper.appendChild(tooltipInput);
  formWrapper.appendChild(requiredWrapper);
  formWrapper.appendChild(formQuestion);
  formWrapper.appendChild(deleteButton);

  formCanvas.appendChild(formWrapper);
  toggleCanvasMsg();

  if (!elementId) {
    // Save the new element
    const elementData = {
      label: labelInput.value,
      placeholder: previewInput.placeholder,
      description: descriptionInput.value,
      tooltip: tooltipInput.value,
      required: requiredCheckbox.checked,
      type: type,
      attributes: inputAttributes
    };

    const savedElementId = await saveFormElement(type, elementData);
    if (savedElementId) {
      formWrapper.dataset.elementId = savedElementId;
    }
  }

  return formWrapper;
};

const createTextArea = async (type = "textarea", savedData = null, elementId = null) => {
  const formWrapper = createFormWrapper("Text Area");
  if (elementId) {
    formWrapper.dataset.elementId = elementId;
  }
  
  // Live Preview Section
  const formQuestion = createElement("div", {
    className: "text-body-medium text-neutral-50",
    style: "border: 1px dashed #ccc; padding: 10px; margin-bottom: 10px; width: 100%; cursor: pointer;",
  });

  const previewLabel = createElement("label", {}, savedData?.label || "Field Label");
  const previewInput = createElement("textarea", {
    placeholder: savedData?.placeholder || "Enter text here...",
    className: "canvas__item--input text-body-medium",
    rows: "4"
  });

  // Field Label Input
  const labelInput = createElement("input", {
    type: "text",
    placeholder: "Set Field Label",
    className: "canvas__item--input text-body-medium label-input",
    value: savedData?.label || ""
  });

  labelInput.addEventListener("input", () => {
    previewLabel.textContent = labelInput.value || "Field Label";
  });

  // Required Checkbox
  const requiredCheckbox = createElement("input", {
    type: "checkbox",
    className: "required-checkbox",
    checked: savedData?.required || false
  });
  const requiredLabel = createElement("label", {}, "Required");

  // Description
  const {descriptionInput, previewDescription} = createFieldDescription();
  if (savedData?.description) {
    descriptionInput.value = savedData.description;
    previewDescription.textContent = savedData.description;
  }

  // Tooltip
  const tooltipInput = createToolTipOption(previewInput);
  if (savedData?.tooltip) {
    tooltipInput.value = savedData.tooltip;
    previewInput.title = savedData.tooltip;
  }

  // Delete Button
  const deleteButton = createDeleteButton();

  // Append elements
  formQuestion.appendChild(previewLabel);
  formQuestion.appendChild(previewInput);
  formQuestion.appendChild(previewDescription);

  formWrapper.appendChild(labelInput);
  formWrapper.appendChild(descriptionInput);
  formWrapper.appendChild(tooltipInput);
  formWrapper.appendChild(requiredCheckbox);
  formWrapper.appendChild(requiredLabel);
  formWrapper.appendChild(formQuestion);
  formWrapper.appendChild(deleteButton);

  formCanvas.appendChild(formWrapper);
  toggleCanvasMsg();

  if (!elementId) {
    // Save the new element
    const elementData = {
      label: labelInput.value,
      placeholder: previewInput.placeholder,
      description: descriptionInput.value,
      tooltip: tooltipInput.value,
      required: requiredCheckbox.checked,
      type: type
    };

    const savedElementId = await saveFormElement(type, elementData);
    if (savedElementId) {
      formWrapper.dataset.elementId = savedElementId;
    }
  }

  return formWrapper;
};

const createDropdown = async (type = "dropdown", savedData = null, elementId = null) => {
  const formWrapper = createFormWrapper("Dropdown");
  if (elementId) {
    formWrapper.dataset.elementId = elementId;
  }

  const formQuestion = createElement("div", {
    className: "text-body-medium text-neutral-50",
    style: "border: 1px dashed #ccc; padding: 10px; margin-bottom: 10px; width: 100%; cursor: pointer;",
  });

  const previewLabel = createElement("label", {}, savedData?.label || "Dropdown Label");
  
  const previewSelect = createElement("select", {
    className: "canvas__item--input text-body-medium",
  });

  // Options container
  const optionsContainer = createElement("div", {
    className: "options-container",
  });

  // Add option button
  const addOptionBtn = createElement("button", {
    type: "button",
    className: "btn btn-add-option",
    textContent: "Add Option"
  });

  addOptionBtn.addEventListener("click", () => {
    const optionInput = createElement("input", {
    type: "text",
      className: "option-input",
      placeholder: "Option text"
    });
    const removeBtn = createElement("button", {
      type: "button",
      className: "btn btn-remove-option",
      textContent: "×"
    });
    const optionWrapper = createElement("div", {
      className: "option-wrapper"
    });
    optionWrapper.appendChild(optionInput);
    optionWrapper.appendChild(removeBtn);
    optionsContainer.appendChild(optionWrapper);

    removeBtn.addEventListener("click", () => optionWrapper.remove());
    
    // Update preview
    optionInput.addEventListener("input", () => {
      const option = createElement("option", {}, optionInput.value);
      previewSelect.appendChild(option);
    });
  });

  // Field Label Input
  const labelInput = createElement("input", {
    type: "text",
    placeholder: "Set Dropdown Label",
    className: "canvas__item--input text-body-medium label-input",
    value: savedData?.label || ""
  });

  labelInput.addEventListener("input", () => {
    previewLabel.textContent = labelInput.value || "Dropdown Label";
  });

  // Required Checkbox
  const requiredCheckbox = createElement("input", {
    type: "checkbox",
    className: "required-checkbox",
    checked: savedData?.required || false
  });
  const requiredLabel = createElement("label", {}, "Required");

  // Description
  const {descriptionInput, previewDescription} = createFieldDescription();
  if (savedData?.description) {
    descriptionInput.value = savedData.description;
    previewDescription.textContent = savedData.description;
  }

  // Load saved options if any
  if (savedData?.options) {
    savedData.options.forEach(option => {
      const optionInput = createElement("input", {
        type: "text",
        className: "option-input",
        value: option
      });
      const removeBtn = createElement("button", {
        type: "button",
        className: "btn btn-remove-option",
        textContent: "×"
      });
      const optionWrapper = createElement("div", {
        className: "option-wrapper"
      });
      optionWrapper.appendChild(optionInput);
      optionWrapper.appendChild(removeBtn);
      optionsContainer.appendChild(optionWrapper);
      removeBtn.addEventListener("click", () => optionWrapper.remove());

      const previewOption = createElement("option", {}, option);
      previewSelect.appendChild(previewOption);
    });
  }

  // Delete Button
  const deleteButton = createDeleteButton();

  // Append elements
  formQuestion.appendChild(previewLabel);
  formQuestion.appendChild(previewSelect);
  formQuestion.appendChild(previewDescription);

  formWrapper.appendChild(labelInput);
  formWrapper.appendChild(descriptionInput);
  formWrapper.appendChild(optionsContainer);
  formWrapper.appendChild(addOptionBtn);
  formWrapper.appendChild(requiredCheckbox);
  formWrapper.appendChild(requiredLabel);
  formWrapper.appendChild(formQuestion);
  formWrapper.appendChild(deleteButton);

  formCanvas.appendChild(formWrapper);
  toggleCanvasMsg();

  if (!elementId) {
    // Save the new element
    const elementData = {
      label: labelInput.value,
      description: descriptionInput.value,
      required: requiredCheckbox.checked,
      type: type,
      options: Array.from(optionsContainer.querySelectorAll('.option-input')).map(input => input.value)
    };

    const savedElementId = await saveFormElement(type, elementData);
    if (savedElementId) {
      formWrapper.dataset.elementId = savedElementId;
    }
  }

  return formWrapper;
};

const createRadioButton = async (type = "radio-button", savedData = null, elementId = null) => {
  const formWrapper = createFormWrapper("Radio Button");
  if (elementId) {
    formWrapper.dataset.elementId = elementId;
  }

  const formQuestion = createElement("div", {
    className: "text-body-medium text-neutral-50",
    style: "border: 1px dashed #ccc; padding: 10px; margin-bottom: 10px; width: 100%; cursor: pointer;",
  });

  const previewLabel = createElement("label", {}, savedData?.label || "Radio Button Label");
  
  const optionsContainer = createElement("div", {
    className: "radio-options-container",
  });

  // Add option button
  const addOptionBtn = createElement("button", {
    type: "button",
    className: "btn btn-add-option",
    textContent: "Add Option"
  });

  const createRadioOption = (value = "") => {
    const optionWrapper = createElement("div", { className: "radio-option-wrapper" });
    const radio = createElement("input", { type: "radio", name: `radio-${Date.now()}` });
    const label = createElement("label", {}, value);
    const removeBtn = createElement("button", {
      type: "button",
      className: "btn btn-remove-option",
      textContent: "×"
    });

    optionWrapper.appendChild(radio);
    optionWrapper.appendChild(label);
    optionWrapper.appendChild(removeBtn);

    removeBtn.addEventListener("click", () => optionWrapper.remove());
    return optionWrapper;
  };

  addOptionBtn.addEventListener("click", () => {
    optionsContainer.appendChild(createRadioOption("New Option"));
  });

  // Field Label Input
  const labelInput = createElement("input", {
    type: "text",
    placeholder: "Set Radio Button Label",
    className: "canvas__item--input text-body-medium label-input",
    value: savedData?.label || ""
  });

  labelInput.addEventListener("input", () => {
    previewLabel.textContent = labelInput.value || "Radio Button Label";
  });

  // Required Checkbox
  const requiredCheckbox = createElement("input", {
    type: "checkbox",
    className: "required-checkbox",
    checked: savedData?.required || false
  });
  const requiredLabel = createElement("label", {}, "Required");

  // Description
  const {descriptionInput, previewDescription} = createFieldDescription();
  if (savedData?.description) {
    descriptionInput.value = savedData.description;
    previewDescription.textContent = savedData.description;
  }

  // Load saved options if any
  if (savedData?.options) {
    savedData.options.forEach(option => {
      optionsContainer.appendChild(createRadioOption(option));
    });
  }

  // Delete Button
  const deleteButton = createDeleteButton();

  // Append elements
  formQuestion.appendChild(previewLabel);
  formQuestion.appendChild(optionsContainer);
  formQuestion.appendChild(previewDescription);

  formWrapper.appendChild(labelInput);
  formWrapper.appendChild(descriptionInput);
  formWrapper.appendChild(addOptionBtn);
  formWrapper.appendChild(requiredCheckbox);
  formWrapper.appendChild(requiredLabel);
  formWrapper.appendChild(formQuestion);
  formWrapper.appendChild(deleteButton);

  formCanvas.appendChild(formWrapper);
  toggleCanvasMsg();

  if (!elementId) {
    // Save the new element
    const elementData = {
      label: labelInput.value,
      description: descriptionInput.value,
      required: requiredCheckbox.checked,
      type: type,
      options: Array.from(optionsContainer.querySelectorAll('.radio-option-wrapper label')).map(label => label.textContent)
    };

    const savedElementId = await saveFormElement(type, elementData);
    if (savedElementId) {
      formWrapper.dataset.elementId = savedElementId;
    }
  }

  return formWrapper;
};

const createDatePicker = async (type = "date-picker", savedData = null, elementId = null) => {
  const formWrapper = createFormWrapper("Date Picker");
  if (elementId) {
    formWrapper.dataset.elementId = elementId;
  }

  const formQuestion = createElement("div", {
    className: "text-body-medium text-neutral-50",
    style: "border: 1px dashed #ccc; padding: 10px; margin-bottom: 10px; width: 100%; cursor: pointer;",
  });

  const previewLabel = createElement("label", {}, savedData?.label || "Date Label");
  
  const previewInput = createElement("input", {
    type: "date",
    className: "canvas__item--input text-body-medium",
    min: savedData?.minDate || "",
    max: savedData?.maxDate || ""
  });

  // Field Label Input
  const labelInput = createElement("input", {
    type: "text",
    placeholder: "Set Date Label",
    className: "canvas__item--input text-body-medium label-input",
    value: savedData?.label || ""
  });

  labelInput.addEventListener("input", () => {
    previewLabel.textContent = labelInput.value || "Date Label";
  });

  // Min Date Input
  const minDateInput = createElement("input", {
    type: "date",
    className: "min-date-input",
    placeholder: "Minimum Date"
  });

  // Max Date Input
  const maxDateInput = createElement("input", {
    type: "date",
    className: "max-date-input",
    placeholder: "Maximum Date"
  });

  // Required Checkbox
  const requiredCheckbox = createElement("input", {
    type: "checkbox",
    className: "required-checkbox",
    checked: savedData?.required || false
  });
  const requiredLabel = createElement("label", {}, "Required");

  // Description
  const {descriptionInput, previewDescription} = createFieldDescription();
  if (savedData?.description) {
    descriptionInput.value = savedData.description;
    previewDescription.textContent = savedData.description;
  }

  // Delete Button
  const deleteButton = createDeleteButton();

  // Append elements
  formQuestion.appendChild(previewLabel);
  formQuestion.appendChild(previewInput);
  formQuestion.appendChild(previewDescription);

  formWrapper.appendChild(labelInput);
  formWrapper.appendChild(descriptionInput);
  formWrapper.appendChild(minDateInput);
  formWrapper.appendChild(maxDateInput);
  formWrapper.appendChild(requiredCheckbox);
  formWrapper.appendChild(requiredLabel);
  formWrapper.appendChild(formQuestion);
  formWrapper.appendChild(deleteButton);

  formCanvas.appendChild(formWrapper);
  toggleCanvasMsg();

  if (!elementId) {
    // Save the new element
    const elementData = {
      label: labelInput.value,
      description: descriptionInput.value,
      required: requiredCheckbox.checked,
      type: type,
      minDate: minDateInput.value,
      maxDate: maxDateInput.value
    };

    const savedElementId = await saveFormElement(type, elementData);
    if (savedElementId) {
      formWrapper.dataset.elementId = savedElementId;
    }
  }

  return formWrapper;
};

const createNumberField = async (type = "number-field", savedData = null, elementId = null) => {
  const formWrapper = createFormWrapper("Number Field");
  if (elementId) {
    formWrapper.dataset.elementId = elementId;
  }

  const formQuestion = createElement("div", {
    className: "text-body-medium text-neutral-50",
    style: "border: 1px dashed #ccc; padding: 10px; margin-bottom: 10px; width: 100%; cursor: pointer;",
  });

  const previewLabel = createElement("label", {}, savedData?.label || "Number Label");
  
  const previewInput = createElement("input", {
    type: "number",
    className: "canvas__item--input text-body-medium",
    min: savedData?.min || "",
    max: savedData?.max || "",
    step: savedData?.step || "1"
  });

  // Field Label Input
  const labelInput = createElement("input", {
    type: "text",
    placeholder: "Set Number Label",
    className: "canvas__item--input text-body-medium label-input",
    value: savedData?.label || ""
  });

  labelInput.addEventListener("input", () => {
    previewLabel.textContent = labelInput.value || "Number Label";
  });

  // Min Value Input
  const minInput = createElement("input", {
    type: "number",
    className: "min-value-input",
    placeholder: "Minimum Value"
  });

  // Max Value Input
  const maxInput = createElement("input", {
    type: "number",
    className: "max-value-input",
    placeholder: "Maximum Value"
  });

  // Step Input
  const stepInput = createElement("input", {
    type: "number",
    className: "step-input",
    placeholder: "Step Value",
    min: "0.000000000000000001",
    value: "1"
  });

  // Required Checkbox
  const requiredCheckbox = createElement("input", {
    type: "checkbox",
    className: "required-checkbox",
    checked: savedData?.required || false
  });
  const requiredLabel = createElement("label", {}, "Required");

  // Description
  const {descriptionInput, previewDescription} = createFieldDescription();
  if (savedData?.description) {
    descriptionInput.value = savedData.description;
    previewDescription.textContent = savedData.description;
  }

  // Delete Button
  const deleteButton = createDeleteButton();

  // Append elements
  formQuestion.appendChild(previewLabel);
  formQuestion.appendChild(previewInput);
  formQuestion.appendChild(previewDescription);

  formWrapper.appendChild(labelInput);
  formWrapper.appendChild(descriptionInput);
  formWrapper.appendChild(minInput);
  formWrapper.appendChild(maxInput);
  formWrapper.appendChild(stepInput);
  formWrapper.appendChild(requiredCheckbox);
  formWrapper.appendChild(requiredLabel);
  formWrapper.appendChild(formQuestion);
  formWrapper.appendChild(deleteButton);

  formCanvas.appendChild(formWrapper);
  toggleCanvasMsg();

  if (!elementId) {
    // Save the new element
    const elementData = {
      label: labelInput.value,
      description: descriptionInput.value,
      required: requiredCheckbox.checked,
      type: type,
      min: minInput.value,
      max: maxInput.value,
      step: stepInput.value
    };

    const savedElementId = await saveFormElement(type, elementData);
    if (savedElementId) {
      formWrapper.dataset.elementId = savedElementId;
    }
  }

  return formWrapper;
};

const createTimePicker = async (type = "time-picker", savedData = null, elementId = null) => {
  const formWrapper = createFormWrapper("Time Picker");
  if (elementId) {
    formWrapper.dataset.elementId = elementId;
  }

  const formQuestion = createElement("div", {
    className: "text-body-medium text-neutral-50",
    style: "border: 1px dashed #ccc; padding: 10px; margin-bottom: 10px; width: 100%; cursor: pointer;",
  });

  const previewLabel = createElement("label", {}, savedData?.label || "Time Label");
  
  const previewInput = createElement("input", {
    type: "time",
    className: "canvas__item--input text-body-medium",
    min: savedData?.minTime || "",
    max: savedData?.maxTime || ""
  });

  // Field Label Input
  const labelInput = createElement("input", {
    type: "text",
    placeholder: "Set Time Label",
    className: "canvas__item--input text-body-medium label-input",
    value: savedData?.label || ""
  });

  labelInput.addEventListener("input", () => {
    previewLabel.textContent = labelInput.value || "Time Label";
  });

  // Min Time Input
  const minTimeInput = createElement("input", {
    type: "time",
    className: "min-time-input",
    placeholder: "Minimum Time"
  });

  // Max Time Input
  const maxTimeInput = createElement("input", {
    type: "time",
    className: "max-time-input",
    placeholder: "Maximum Time"
  });

  // Required Checkbox
  const requiredCheckbox = createElement("input", {
    type: "checkbox",
    className: "required-checkbox",
    checked: savedData?.required || false
  });
  const requiredLabel = createElement("label", {}, "Required");

  // Description
  const {descriptionInput, previewDescription} = createFieldDescription();
  if (savedData?.description) {
    descriptionInput.value = savedData.description;
    previewDescription.textContent = savedData.description;
  }

  // Delete Button
  const deleteButton = createDeleteButton();

  // Append elements
  formQuestion.appendChild(previewLabel);
  formQuestion.appendChild(previewInput);
  formQuestion.appendChild(previewDescription);

  formWrapper.appendChild(labelInput);
  formWrapper.appendChild(descriptionInput);
  formWrapper.appendChild(minTimeInput);
  formWrapper.appendChild(maxTimeInput);
  formWrapper.appendChild(requiredCheckbox);
  formWrapper.appendChild(requiredLabel);
  formWrapper.appendChild(formQuestion);
  formWrapper.appendChild(deleteButton);

  formCanvas.appendChild(formWrapper);
  toggleCanvasMsg();

  if (!elementId) {
    // Save the new element
    const elementData = {
      label: labelInput.value,
      description: descriptionInput.value,
      required: requiredCheckbox.checked,
      type: type,
      minTime: minTimeInput.value,
      maxTime: maxTimeInput.value
    };

    const savedElementId = await saveFormElement(type, elementData);
    if (savedElementId) {
      formWrapper.dataset.elementId = savedElementId;
    }
  }

  return formWrapper;
};

const createToggleSwitch = () => {
  const formWrapper = createFormWrapper("Toggle Switch");

  // Live Preview Section
  const formQuestion = createElement("div", {
    className: "text-body-medium text-neutral-50",
    style: "border: 1px dashed #ccc; padding: 10px; margin-bottom: 10px; width: 100%; cursor: pointer;",
  });

  const previewLabel = createElement("label", {}, "Field Label");
  const toggleWrapper = createElement("div", {
    style: "display: flex; align-items: center; gap: 8px;",
  });

  const previewInput = createElement("input", {
    type: "checkbox",
    className: "toggle-switch",
  });

  const toggleLabel = createElement("span", {}, "Off");

  previewInput.addEventListener("change", () => {
    toggleLabel.textContent = previewInput.checked ? "On" : "Off";
  });

  const {descriptionInput, previewDescription} = createFieldDescription();

  toggleWrapper.appendChild(previewInput);
  toggleWrapper.appendChild(toggleLabel);
  formQuestion.appendChild(previewLabel);
  formQuestion.appendChild(toggleWrapper);
  formQuestion.appendChild(previewDescription);

  // Settings Section
  const settingsSection = createElement("div", {
    className: "text-body-medium text-neutral-50",
    style: "display: flex; gap: 4px; flex-wrap: wrap; border: 1px dashed #ccc; padding: 10px; margin-bottom: 10px; width: 100%",
  });

  // Label Input
  const labelInput = createElement("input", {
    type: "text",
    placeholder: "Set field label",
    className: "canvas__item--input label-input text-body-medium",
  });

  labelInput.addEventListener("input", () => {
    previewLabel.textContent = labelInput.value || "Field Label";
  });

  // On Text Input
  const onTextInput = createElement("input", {
    type: "text",
    placeholder: "On Text",
    value: "On",
    className: "canvas__item--input text-body-medium",
  });

  // Off Text Input
  const offTextInput = createElement("input", {
    type: "text",
    placeholder: "Off Text",
    value: "Off",
    className: "canvas__item--input text-body-medium",
  });

  const updateToggleLabel = () => {
    toggleLabel.textContent = previewInput.checked ? onTextInput.value : offTextInput.value;
  };

  onTextInput.addEventListener("input", updateToggleLabel);
  offTextInput.addEventListener("input", updateToggleLabel);
  previewInput.addEventListener("change", updateToggleLabel);

  // Required Toggle
  const requiredToggleWrapper = createElement("div", {
    style: "display: flex; gap: 4px;",
  });

  const requiredToggle = createElement("input", {
    type: "checkbox",
    className: "required-toggle",
  });

  const requiredLabel = createElement("label", {
    className: "text-body-medium text-neutral-50",
  }, "Required");

  requiredToggle.addEventListener("change", () => {
    previewInput.required = requiredToggle.checked;
  });

  requiredToggleWrapper.appendChild(requiredToggle);
  requiredToggleWrapper.appendChild(requiredLabel);

  // Append all settings
  settingsSection.appendChild(descriptionInput);
  settingsSection.appendChild(labelInput);
  settingsSection.appendChild(onTextInput);
  settingsSection.appendChild(offTextInput);
  settingsSection.appendChild(requiredToggleWrapper);
  settingsSection.appendChild(createToolTipOption(previewInput));

  // Final Assembly
  formWrapper.appendChild(formQuestion);
  formWrapper.appendChild(settingsSection);
  formWrapper.appendChild(createDeleteButton());

  return formWrapper;
};

const createLayoutElement = async (type = "section-break", savedData = null, elementId = null) => {
  let title = "";
  switch(type) {
    case "section-break":
      title = "Section Break";
      break;
    case "page-break":
      title = "Page Break";
      break;
    case "divider":
      title = "Divider";
      break;
    case "heading":
      title = "Heading";
      break;
  }
  
  const formWrapper = createFormWrapper(title);
  if (elementId) {
    formWrapper.dataset.elementId = elementId;
  }

  const formQuestion = createElement("div", {
    className: "text-body-medium text-neutral-50",
    style: "border: 1px dashed #ccc; padding: 10px; margin-bottom: 10px; width: 100%;"
  });

  // Settings Section for editing the layout elements
  const settingsSection = createElement("div", {
    className: "settings-section",
    style: "margin-bottom: 10px;"
  });

  let content;
  let settingsInput;
  
  switch(type) {
    case "section-break":
      // Section Break Settings
      settingsInput = createElement("input", {
        type: "text",
        className: "canvas__item--input text-body-large",
        placeholder: "Enter Section Title",
        value: savedData?.title || ""
      });
      settingsSection.appendChild(settingsInput);
      
      // Section Break Display
      content = createElement("div", {
        className: "section-break",
        style: "border-top: 2px solid #ccc; margin: 20px 0; padding-top: 20px;"
      });
      const sectionTitle = createElement("h3", {
        className: "section-title text-body-large",
        style: "margin: 0; color: #333;"
      });
      sectionTitle.textContent = savedData?.title || "Section Title";
      settingsInput.addEventListener("input", () => {
        sectionTitle.textContent = settingsInput.value || "Section Title";
      });
      content.appendChild(sectionTitle);
      break;
      
    case "page-break":
      content = createElement("div", {
        className: "page-break",
        style: "border-top: 2px dashed #ccc; margin: 20px 0; text-align: center; position: relative;"
      });
      const pageBreakLabel = createElement("span", {
        style: "position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: white; padding: 0 10px; color: #666;"
      });
      pageBreakLabel.textContent = "Page Break";
      content.appendChild(pageBreakLabel);
      break;
      
    case "divider":
      content = createElement("hr", {
        className: "divider",
        style: "border: none; border-top: 1px solid #ccc; margin: 10px 0;"
      });
      break;
      
    case "heading":
      // Heading Settings
      settingsInput = createElement("input", {
        type: "text",
        className: "canvas__item--input text-heading-medium",
        placeholder: "Enter Heading Text",
        value: savedData?.text || ""
      });
      settingsSection.appendChild(settingsInput);
      
      // Heading Display
      content = createElement("h2", {
        className: "form-heading text-heading-medium",
        style: "margin: 0; color: #333;"
      });
      content.textContent = savedData?.text || "Heading";
      settingsInput.addEventListener("input", () => {
        content.textContent = settingsInput.value || "Heading";
      });
      break;
  }

  // Delete Button
  const deleteButton = createDeleteButton();

  // Append elements
  formQuestion.appendChild(content);
  if (settingsInput) {
    formWrapper.appendChild(settingsSection);
  }
  formWrapper.appendChild(formQuestion);
  formWrapper.appendChild(deleteButton);

  formCanvas.appendChild(formWrapper);
  toggleCanvasMsg();

  if (!elementId) {
    // Save the new element
    const elementData = {
      type: type,
      title: type === "section-break" ? settingsInput?.value : null,
      text: type === "heading" ? settingsInput?.value : null
    };

    const savedElementId = await saveFormElement(type, elementData);
    if (savedElementId) {
      formWrapper.dataset.elementId = savedElementId;
    }
  }

  return formWrapper;
};

const formRenderer = {
  "text-field": createTextField,
  "text-area": createTextArea,
  "dropdown": createDropdown,
  "radio-button": createRadioButton,
  "date-picker": createDatePicker,
  "number-field": createNumberField,
  "time-picker": createTimePicker,
  "toggle-switch": createToggleSwitch,
  "email-field": (type, savedData, elementId) => createTextField("email-field", savedData, elementId),
  "phone-field": (type, savedData, elementId) => createTextField("phone-field", savedData, elementId),
  "checkbox": createToggleSwitch,
  "multi-select": createDropdown,
  "date-range": createDatePicker,
  "section-break": (type, savedData, elementId) => createLayoutElement("section-break", savedData, elementId),
  "page-break": (type, savedData, elementId) => createLayoutElement("page-break", savedData, elementId),
  "divider": (type, savedData, elementId) => createLayoutElement("divider", savedData, elementId),
  "heading": (type, savedData, elementId) => createLayoutElement("heading", savedData, elementId),
  "file-upload": (type, savedData, elementId) => createTextField("file-upload", savedData, elementId),
  "image-upload": (type, savedData, elementId) => createTextField("image-upload", savedData, elementId),
  "document-upload": (type, savedData, elementId) => createTextField("document-upload", savedData, elementId),
  "signature-field": (type, savedData, elementId) => createTextField("signature-field", savedData, elementId),
  "rating-scale": createRadioButton,
  "captcha": createFormWrapper,
  "terms-checkbox": createToggleSwitch,
  "url-field": (type, savedData, elementId) => createTextField("url-field", savedData, elementId),
  "color-picker": (type, savedData, elementId) => createTextField("color-picker", savedData, elementId),
  "address-field": createTextArea,
  "rich-text": createTextArea,
  "price-field": (type, savedData, elementId) => createTextField("price-field", savedData, elementId),
  "calculation-field": (type, savedData, elementId) => createTextField("calculation-field", savedData, elementId),
  "rating-stars": createRadioButton
};

// formCanvas.appendChild(createFormWrapper("testing"))

// implement dragover and drop event
formCanvas.addEventListener("dragover", (event) => {
  event.preventDefault();
});

formCanvas.addEventListener("drop", async (event) => {
  event.preventDefault();
  const type = event.dataTransfer.getData("text/plain");
  if (formRenderer[type]) {
    const element = await formRenderer[type](type);
    if (element) {
      formCanvas.appendChild(element);
    toggleCanvasMsg();
    }
  }
});

// Function to load and render saved form elements
async function loadSavedFormElements() {
    const formId = new URLSearchParams(window.location.search).get('id');
    if (!formId) return;

    try {
    const response = await fetch(`../backend/api/get_form_elements.php?formId=${formId}`);
    const result = await response.json();

    if (result.success && result.elements) {
      // Clear existing elements except the first child (if it's a title or header)
      while (formCanvas.children.length > 1) {
                formCanvas.removeChild(formCanvas.lastChild);
            }
            
      // Sort elements by position
      result.elements.sort((a, b) => a.position - b.position);

      // Load each element
      for (const element of result.elements) {
        const renderer = formRenderer[element.element_type];
        if (renderer) {
          await renderer(element.element_type, element.element_data, element.id);
        }
      }
      
      toggleCanvasMsg();
    }
  } catch (error) {
    console.error('Error loading form elements:', error);
  }
}

// Call the function when the page loads
document.addEventListener('DOMContentLoaded', loadSavedFormElements);

// Add this function at the end of the file
async function saveFormElement(type, elementData) {
  const formId = new URLSearchParams(window.location.search).get('id');
  if (!formId) return null;

  try {
    const response = await fetch('../backend/api/save_form_element.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        formId,
        type,
        data: elementData,
        position: formCanvas.children.length - 1
      })
    });

    const result = await response.json();
    if (result.success) {
      return result.elementId;
    } else {
      console.error('Failed to save form element:', result.error);
      return null;
        }
    } catch (error) {
    console.error('Error saving form element:', error);
    return null;
    }
}

