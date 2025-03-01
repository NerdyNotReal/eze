const sidebarData = {
  basic: {
    "Text Inputs": [
      { label: "Text Field", type: "text-field" },
      { label: "Text Area", type: "text-area" },
      { label: "Number Field", type: "number-field" },
      { label: "Email Field", type: "email-field" },
      { label: "Phone Field", type: "phone-field" },
      { label: "URL Field", type: "url-field" }
    ],
    "Choice Inputs": [
      { label: "Dropdown", type: "dropdown" },
      { label: "Radio Button", type: "radio-button" },
      { label: "Checkbox", type: "checkbox" },
      { label: "Toggle Switch", type: "toggle-switch" },
      { label: "Multi Select", type: "multi-select" }
    ],
    "Date & Time": [
      { label: "Date Picker", type: "date-picker" },
      { label: "Time Picker", type: "time-picker" },
      { label: "Date Range", type: "date-range" }
    ],
    "Layout Elements": [
      { label: "Section Break", type: "section-break" },
      { label: "Page Break", type: "page-break" },
      { label: "Divider", type: "divider" },
      { label: "Heading", type: "heading" }
    ]
  }
};

const sidebarContent = document.querySelector(".sidebar__content");

// Function to clear sidebar content
const clearSidebarContent = () => {
  while (sidebarContent.firstChild) {
    sidebarContent.removeChild(sidebarContent.firstChild);
  }
};

const generateSidebar = (data) => {
  // Clear existing content first
  clearSidebarContent();

  Object.entries(data).forEach(([tabId, sections], index) => {
    const sidebarSection = document.createElement("div");
    sidebarSection.classList.add("sidebar__section");
    sidebarSection.id = tabId;

    if (index === 0) {
      sidebarSection.classList.add("sidebar__section--active");
    }

    Object.entries(sections).forEach(([sectionTitle, items]) => {
      const sidebarItems = document.createElement("div");
      sidebarItems.classList.add("sidebar__items");
      
      const sidebarSectionTitle = document.createElement("h3");
      sidebarSectionTitle.classList.add("sidebar__section-title", "text-body-medium");
      sidebarSectionTitle.textContent = sectionTitle;

      sidebarItems.appendChild(sidebarSectionTitle);

      items.forEach(({ label, type }) => {
        const sidebarItem = document.createElement("div");
        sidebarItem.classList.add("sidebar__item");
        sidebarItem.setAttribute("draggable", "true");
        sidebarItem.setAttribute("data-type", type);
        sidebarItem.setAttribute("data-filter", label.toLowerCase());

        const sidebarItemIcon = document.createElement("span");
        sidebarItemIcon.classList.add("sidebar__item-icon");

        const sidebarItemLabel = document.createElement("p");
        sidebarItemLabel.classList.add("sidebar__item-label", "text-body-small");
        sidebarItemLabel.textContent = label;

        sidebarItem.appendChild(sidebarItemIcon);
        sidebarItem.appendChild(sidebarItemLabel);
        sidebarItems.appendChild(sidebarItem);
      });
      sidebarSection.appendChild(sidebarItems);
    });
    sidebarContent.appendChild(sidebarSection);
  });

  // Set up drag events after generating sidebar
  setupDragEvents();
};

// Separate function for setting up drag events
const setupDragEvents = () => {
  document.querySelectorAll(".sidebar__item").forEach((item) => {
    item.addEventListener("dragstart", (event) => {
      event.dataTransfer.setData("text/plain", event.target.dataset.type);
    });
  });
};

// Initialize sidebar only once when the document is ready
document.addEventListener('DOMContentLoaded', () => {
  generateSidebar(sidebarData);
});


//implement tabs switching
const activeTab = (clickedTab) => {
    document.querySelectorAll('.sidebar__tab').forEach(tab => {
        tab.classList.remove('sidebar__tab--active');
        tab.setAttribute('aria-selected', 'false');
    });

    clickedTab.classList.add('sidebar__tab--active');
    clickedTab.setAttribute('aria-selected', 'true');

    document.querySelectorAll('.sidebar__section').forEach(section => {
        section.classList.remove('sidebar__section--active');
    });

    const target = clickedTab.dataset.tab;
    document.querySelector(`#${target}`).classList.add('sidebar__section--active');

}

document.querySelectorAll('.sidebar__tab').forEach(tab => {
    tab.addEventListener('click', (event) => {
        activeTab(event.target);
    })
})


// implementing search/filtering
const searchInput = document.querySelector(".sidebar__search-input");
const sidebarSectionTitle = document.querySelectorAll(".sidebar__section-title");

const filterSidebar = (input) => {
    const inputLowercase = input.toLowerCase().trim();
    const sections = document.querySelectorAll('.sidebar__section');
    
    sections.forEach(section => {
        let hasVisibleItems = false;
        const items = section.querySelectorAll('.sidebar__item');
        
        items.forEach(item => {
            const itemFilter = item.getAttribute('data-filter') || '';
            const isMatch = itemFilter.toLowerCase().includes(inputLowercase);
            item.style.display = isMatch ? 'flex' : 'none';
            if (isMatch) hasVisibleItems = true;
        });
        
        // Show/hide section titles based on whether they have matching items
        const sectionTitles = section.querySelectorAll('.sidebar__section-title');
        sectionTitles.forEach(title => {
            title.style.display = hasVisibleItems || inputLowercase === '' ? 'block' : 'none';
        });
    });
};

searchInput.addEventListener('input', (event) => {
    filterSidebar(event.target.value);
});

