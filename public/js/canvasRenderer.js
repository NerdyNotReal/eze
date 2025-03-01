// ---Canvas global---

const formcanvas = document.querySelector("#form-canvas");
const formcanvasMsg = document.querySelector(".form-canvas--message");

//global functions
const toggleCanvasMsg = () => {
  if (formcanvas.childElementCount > 1) {
    formcanvasMsg.style.display = "none";
    console.log(`Item count: ${formcanvas.childElementCount - 1}`);
  } else {
    formcanvasMsg.style.display = "flex";
  }
};



const createPlaceholderInput = () => {
  const input = document.createElement("input");
  input.type = "text";
  input.className = "canvas__item--input canvas__item--input--placeholder text-body-small";
  input.placeholder = "Enter Placeholder";
  return input;
}

const createQuestionInput = () => {
  const input = document.createElement("input");
  input.type = "text";
  input.className = "canvas__item--input canvas__item--input-label text-body-small";
  input.placeholder = "Enter question";
  return input;
}

const createHelperInput = () => {
  const input = document.createElement("input");
  input.type = "text";
  input.className = "canvas__item--input canvas__item--input-helper text-body-small";
  input.placeholder = "Enter helper note";
  return input;
}


const createRequiredToggle = () => {
  const requiredToggle = document.createElement("input");
  requiredToggle.type = "checkbox";
  requiredToggle.className = "canvas__item--input canvas__item--required-toggle";
  const requiredLabel = document.createElement("label");
  requiredLabel.className = "canvas__item--required-label text-body-medium";
  requiredLabel.textContent = "Required";
  requiredLabel.appendChild(requiredToggle);
  return requiredLabel;
  
}

const createErrorMsgInput = () => {
  const errorMsgInput = document.createElement("input");
  errorMsgInput.className = "canvas__item--input canvas__item--input--error-msg text-body-small";
  errorMsgInput.placeholder = "Enter Error Msg";
  return errorMsgInput;
}


const createmaxCharsInput = () => {
  const maxValueInput = document.createElement("input");
  maxValueInput.type = "number";
  maxValueInput.className = "canvas__item--input canvas__item--max-value text-body-small";
  maxValueInput.placeholder = "max value";
  
  return maxValueInput;
}

const createMinCharsInput = () => {
  const minValueInput = document.createElement("input");
  minValueInput.type = "number";
  minValueInput.className = "canvas__item--input canvas__item--min-value text-body-small";
  minValueInput.placeholder = "Min value";
  
  return minValueInput;
}

const createRegexPattern = () => {
  const regexSelect = document.createElement("select");
  regexSelect.className = "canvas__item--input canvas__item--regex-select text-body-small";

  // Define regex validation options
  const regexOptions = [
    { value: "", text: "Choose redgex pattern" },
    { value: "^[a-zA-Z0-9 ]*$", text: "Alphanumeric" },
    { value: "^[0-9]*$", text: "Numeric" },
    { value: "^[a-zA-Z ]*$", text: "Alphabetic" },
    { value: "^[^@\s]+@[^@\s]+\.[^@\s]+$", text: "Email" },
    { value: "custom", text: "Custom (Provide your own)" },
  ];

  //ropdown with options
  regexOptions.forEach(option => {
    const optionElement = document.createElement("option");
    optionElement.value = option.value;
    optionElement.textContent = option.text;
    regexSelect.appendChild(optionElement);
  });

  // Add event listener for custom regex input
  regexSelect.addEventListener("change", (event) => {
    if (event.target.value === "custom") {
      const customRegexInput = document.createElement("input");
      customRegexInput.type = "text";
      customRegexInput.className = "canvas__item--input canvas__item--custom-regex text-body-small";
      customRegexInput.placeholder = "Enter custom regex pattern";
      
      regexSelect.parentNode.insertBefore(customRegexInput, regexSelect.nextSibling);
    } else {
      const existingCustomInput = regexSelect.parentNode.querySelector(".canvas__item--custom-regex");
      if (existingCustomInput) {
        existingCustomInput.remove();
      }
    }
  });

  return regexSelect;
};

const createDeleteButton = () => {
  const deleteButton = document.createElement("button");
  deleteButton.textContent = "Remove";
  deleteButton.classList.add("btn", "btn__remove");

  // delete items functionality
  deleteButton.addEventListener("click", (event) => {
    const canvasItem = event.target.closest(".canvas__item");
    if (canvasItem) {
      canvasItem.remove();
      toggleCanvasMsg();
    }
  });

  return deleteButton;
};


// function for creating common field like question input, palceholer, required btn etc.
const createFormFields = (includePlaceholder) => {
  // const elements = {};
  // elements.inputQuestion = createQuestionInput();
  // elements.helperNote = createHelperInput();
  // elements.errorMsgInput = createErrorMsgInput();
  // elements.requiredToggle = createRequiredToggle();

  // if (includePlaceholder) {
  //   elements.placeholderInput = createPlaceholderInput();
  // }
  // return elements;

  const fragment = document.createDocumentFragment();
  fragment.appendChild(createRequiredToggle());
  fragment.appendChild(createQuestionInput());
  fragment.appendChild(createHelperInput());
  
  if(includePlaceholder) {
    fragment.appendChild(createPlaceholderInput());

  }
  fragment.appendChild(createErrorMsgInput());

  
  return fragment;
};



const createFormWrapper = (label) => {
  const canvasItem = document.createElement("div");
  canvasItem.className = "canvas__item";

  const itemType = document.createElement("p");
  itemType.className = "canvas__item--type";
  itemType.textContent = label;
  canvasItem.appendChild(itemType);

  const customizeText = document.createElement("p");
  customizeText.className = "text-body-medium text-neutral-50";
  customizeText.textContent = "Customize";
  canvasItem.appendChild(customizeText);


  

  return canvasItem;
};

// actual canvas items
  // text field
  const createTextField = () => {
  const formsWrapper = createFormWrapper("Text Field")

  const formFields = createFormFields(true);
  
  formsWrapper.appendChild(formFields);

  formsWrapper.appendChild(createMinCharsInput());
  formsWrapper.appendChild(createmaxCharsInput());
  formsWrapper.appendChild(createRegexPattern());


  formsWrapper.appendChild(createDeleteButton());

  return formsWrapper;
  }


  const createTextArea = () => {
    const formsWrapper = createFormWrapper("Text Area")
  
    const formFields = createFormFields(true);
    
    formsWrapper.appendChild(formFields);


    return formsWrapper;
  }


  const createDropdown = () => {
    const formsWrapper = createFormWrapper("Drop Down");

    const formFields =createFormFields(true);
    formsWrapper.appendChild(formFields.requiredToggle);
  }
  



const formRenderer = {
  "text-field" : (canvas) => {
    const field = createTextField();
    canvas.appendChild(field);
  },
  "text-area" : (canvas) => {
    const field = createTextArea();
    canvas.appendChild(field);
  }

}




// implement dragover/drop event

formcanvas.addEventListener("dragover", (event) => {
  event.preventDefault();
});


formcanvas.addEventListener("drop", (event) => {
    event.preventDefault();

    const type = event.dataTransfer.getData("text/plain");
    if (formRenderer[type]) {
      formRenderer[type](formcanvas);
      toggleCanvasMsg();
    }
    
})



