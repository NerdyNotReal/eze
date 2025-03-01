<section id="sidebar">
  <div class="sidebar__headers">
    <div class="sidebar__tabs" role="tablist">
      <button
        class="sidebar__tab sidebar__tab--active text-body-medium"
        data-tab="basic"
        aria-selected="true"
      >
        Form Fields
      </button>
    </div>

    <div class="sidebar__search">
      <input
        type="search"
        class="search-input sidebar__search-input"
        placeholder="Search fields..."
      />
    </div>
  </div>
  
  <div class="sidebar__content">
    <div class="sidebar__section sidebar__section--active" id="basic">
      <div class="sidebar__items">
        <h3 class="sidebar__section-title text-body-medium">Text Inputs</h3>
        <div class="sidebar__item" draggable="true" data-type="text-field">
          <span class="sidebar__item-icon"></span>
          <span class="sidebar__item-label">Text Field</span>
        </div>
        <div class="sidebar__item" draggable="true" data-type="text-area">
          <span class="sidebar__item-icon"></span>
          <span class="sidebar__item-label">Text Area</span>
        </div>
        <div class="sidebar__item" draggable="true" data-type="number-field">
          <span class="sidebar__item-icon"></span>
          <span class="sidebar__item-label">Number Field</span>
        </div>
      </div>

      <div class="sidebar__items">
        <h3 class="sidebar__section-title text-body-medium">Choice Inputs</h3>
        <div class="sidebar__item" draggable="true" data-type="dropdown">
          <span class="sidebar__item-icon"></span>
          <span class="sidebar__item-label">Dropdown</span>
        </div>
        <div class="sidebar__item" draggable="true" data-type="radio-button">
          <span class="sidebar__item-icon"></span>
          <span class="sidebar__item-label">Radio Button</span>
        </div>
        <div class="sidebar__item" draggable="true" data-type="toggle-switch">
          <span class="sidebar__item-icon"></span>
          <span class="sidebar__item-label">Toggle Switch</span>
        </div>
      </div>

      <div class="sidebar__items">
        <h3 class="sidebar__section-title text-body-medium">Date & Time</h3>
        <div class="sidebar__item" draggable="true" data-type="date-picker">
          <span class="sidebar__item-icon"></span>
          <span class="sidebar__item-label">Date Picker</span>
        </div>
        <div class="sidebar__item" draggable="true" data-type="time-picker">
          <span class="sidebar__item-icon"></span>
          <span class="sidebar__item-label">Time Picker</span>
        </div>
      </div>

      <div class="sidebar__items">
        <h3 class="sidebar__section-title text-body-medium">Layout Elements</h3>
        <div class="sidebar__item" draggable="true" data-type="section-break">
          <span class="sidebar__item-icon"></span>
          <span class="sidebar__item-label">Section Break</span>
        </div>
        <div class="sidebar__item" draggable="true" data-type="page-break">
          <span class="sidebar__item-icon"></span>
          <span class="sidebar__item-label">Page Break</span>
        </div>
        <div class="sidebar__item" draggable="true" data-type="divider">
          <span class="sidebar__item-icon"></span>
          <span class="sidebar__item-label">Divider</span>
        </div>
        <div class="sidebar__item" draggable="true" data-type="heading">
          <span class="sidebar__item-icon"></span>
          <span class="sidebar__item-label">Heading</span>
        </div>
      </div>
    </div>
  </div>
</section>
