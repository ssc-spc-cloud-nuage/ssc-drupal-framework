class SSCTabs extends HTMLElement {
  #activeIndex = 0;
  #tabs = [];
  #panels = [];
  #navItems = [];
  #contentEl = null;
  #accordionBreakpoint = 500;
  #accordionResizeObserver = null;
  #onAccordionResize = (entries) => {
    const [entry] = entries;
    if (!entry) return;

    const size =
      entry.borderBoxSize?.[0]?.inlineSize ?? entry.contentRect.width;
    this.#syncLayoutMode(size <= this.#accordionBreakpoint);
    this.#showTab(this.#activeIndex);
  };
  #getTabTitle(item, index) {
    return item.getAttribute("data-tab-title") || `Tab ${index + 1}`;
  }

  constructor() {
    super();
    this.attachShadow({ mode: "open" });
  }

  connectedCallback() {
    this.#render();
    this.#bindEvents();
    this.#setupAccordionQuery();
    this.#showTab(this.#activeIndex);
  }

  disconnectedCallback() {
    this.#teardownAccordionQuery();
  }

  #render() {
    const items = [...this.children].filter((child) =>
      child.hasAttribute("data-tab-title"),
    );

    items.forEach((item, index) => {
      item.setAttribute("slot", `panel-${index}`);
    });

    const tabButtons = items
      .map((item, index) => {
        const title = this.#getTabTitle(item, index);
        return `
          <li class="ssc-tabs__nav-item">
            <button
              class="ssc-tabs__button"
              type="button"
              role="tab"
              aria-selected="false"
              aria-controls="ssc-tabs-panel-${index}"
              id="ssc-tabs-tab-${index}"
            >
              <span class="ssc-tabs__button-label">${title}</span>
              <svg
                class="ssc-tabs__button-icon"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
              >
                <path class="ssc-tabs__button-icon-path" d="M5 12h14"></path>
                <path class="ssc-tabs__button-icon-path" d="M12 5v14"></path>
              </svg>
            </button>
          </li>
        `;
      })
      .join("");

    const panelEls = items
      .map(
        (item, index) =>
          `
            <section
              class="ssc-tabs__panel"
              role="tabpanel"
              id="ssc-tabs-panel-${index}"
              aria-labelledby="ssc-tabs-tab-${index}"
              hidden
            >
              <div class="ssc-tabs__panel-inner">
                <h3 class="ssc-tabs__panel-title">
                  ${this.#getTabTitle(item, index)}
                </h3>
                <slot name="panel-${index}"></slot>
              </div>
            </section>
          `,
      )
      .join("");

    this.shadowRoot.innerHTML = `
      <style>
        *,
        *::before,
        *::after {
          box-sizing: border-box;
        }

        :host {
          --ssc-tabs-bg-primary: #000;
          --ssc-tabs-bg-secondary: var(--clr-ssc-slate-800);
          --ssc-tabs-text-primary: #fff;
          --ssc-tabs-text-secondary: var(--clr-ssc-slate-400);
          --ssc-tabs-border-size: 4px;

          display: block;
          inline-size: 100%;
          container-type: inline-size;
          color: var(--ssc-tabs-text-primary);
          isolation: isolate;
        }

        .ssc-tabs {
          display: grid;
          gap: 1.5rem;
          min-width: 0;
        }

        :host([variant="side"]) {
          & .ssc-tabs {
            grid-template-columns: clamp(180px, 34%, 320px) minmax(0, 1fr);
            align-items: stretch;

            @container (max-width: ${this.#accordionBreakpoint}px) {
              grid-template-columns: 1fr;
            }
          }
        }

        :host([variant="top"]) {
          & .ssc-tabs__nav {
            display: flex;
            flex-wrap: wrap;
          }

          & .ssc-tabs__button {
            width: auto;
            white-space: nowrap;
          }
        }

        .ssc-tabs__nav {
          position: relative;
          display: grid;
          height: fit-content;
          margin: 0;
          padding: 0;
          list-style: none;
          anchor-scope: --ssc-tabs-active;

          &::before {
            content: "";
            position: absolute;
            position-anchor: --ssc-tabs-active;
            z-index: -1;
            top: anchor(top);
            right: anchor(right);
            bottom: anchor(bottom);
            left: anchor(left);
            background-color: var(--ssc-tabs-bg-secondary);
            border-radius: var(--ssc-tabs-border-size);
            transition: inset 200ms ease;
          }
        }

        .ssc-tabs__button {
          position: relative;
          display: flex;
          align-items: center;
          gap: 0.75rem;
          width: 100%;
          padding: 0.75rem 1rem;
          background: transparent;
          border: 0;
          border-radius: var(--ssc-tabs-border-size);
          color: var(--ssc-tabs-text-secondary);
          font: inherit;
          line-height: 1.4;
          text-align: left;
          cursor: pointer;
          transition: color 160ms ease;

          &:hover {
            color: white;
          }
          &::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: -2;
            border-radius: var(--ssc-tabs-border-size);
            transition: background-color 120ms ease;
          }

          &:hover::before {
            background-color: color-mix(in srgb, var(--ssc-tabs-bg-primary), transparent 70%);
          }

          &[aria-selected="true"] {
            anchor-name: --ssc-tabs-active;
            color: var(--ssc-tabs-text-primary);
            cursor: default;
          }
        }

        .ssc-tabs__button-label {
          min-width: 0;
        }

        .ssc-tabs__button-icon {
          display: none;
          flex: 0 0 auto;
          width: 1rem;
          height: 1rem;
          margin-left: auto;
        }

        .ssc-tabs__button {
          @supports not (position-anchor: --ssc-tabs-active) {
            &[aria-selected="true"] {
              background-color: var(--ssc-tabs-bg-secondary);
            }
          }
        }

        .ssc-tabs__content {
          min-width: 0;
          min-height: 100%;
          padding: 1.5rem;
          border-radius: 8px;
          background-color: color-mix(in srgb, var(--ssc-tabs-bg-primary), transparent 75%);
        }

        .ssc-tabs__panel {
          line-height: 1.5;

          & .ssc-tabs__panel-title {
            margin: 0 0 0.75rem;
            color: var(--ssc-tabs-text-secondary);
            font-size: 1.25rem;
          }
        }

        :host([data-layout="accordion"]) {
          & .ssc-tabs {
            display: block;
          }

          & .ssc-tabs__nav {
            display: block;

            &::before {
              content: none;
            }
          }

          & .ssc-tabs__button {
            width: 100%;
            border: 1px solid transparent;
          }

          & .ssc-tabs__button-icon {
            display: block;
            transition:
              opacity 200ms ease,
              scale 200ms ease;

            .ssc-tabs__button[aria-selected="true"] & {
              opacity: 0;
              scale: 0.75;
            }
          }

          & .ssc-tabs__button[aria-selected="true"] {
            background-color: var(--ssc-tabs-bg-secondary);
            border: 1px solid var(--ssc-tabs-bg-secondary);
            border-bottom-color: transparent;
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
          }

          & .ssc-tabs__content {
            display: none;
          }

          & .ssc-tabs__panel {
            display: grid;
            grid-template-rows: 0fr;
            margin-top: 0;
            padding-block: 0;
            padding-inline: 1rem;
            background-color: color-mix(in srgb, var(--ssc-tabs-bg-primary), transparent 75%);
            border: 1px solid var(--ssc-tabs-bg-secondary);
            border-color: transparent;
            border-width: 0 1px 1px;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
            border-bottom-left-radius: var(--ssc-tabs-border-size);
            border-bottom-right-radius: var(--ssc-tabs-border-size);
            transition: grid-template-rows 280ms ease, padding-block 280ms ease,
              border-width 280ms ease;
          }

          & .ssc-tabs__panel[data-expanded="true"] {
            grid-template-rows: 1fr;
            padding-block: 1rem;
            border: 1px solid var(--ssc-tabs-bg-secondary);
          }

          & .ssc-tabs__panel-inner {
            min-height: 0;
            overflow: clip;
          }

          & .ssc-tabs__panel[hidden] {
            display: block;
          }

          & .ssc-tabs__panel-title {
            display: none;
          }

          & .ssc-tabs__panel-inner > slot {
            display: block;
          }

          & .ssc-tabs__panel ::slotted(*) {
            display: flow-root;
            margin-block: 0;
          }
        }
      </style>

      <div class="ssc-tabs">
        <ul class="ssc-tabs__nav" role="tablist">
          ${tabButtons}
        </ul>
        <div class="ssc-tabs__content">
          ${panelEls}
        </div>
      </div>
    `;

    this.#tabs = [...this.shadowRoot.querySelectorAll(".ssc-tabs__button")];
    this.#panels = [...this.shadowRoot.querySelectorAll(".ssc-tabs__panel")];
    this.#navItems = [
      ...this.shadowRoot.querySelectorAll(".ssc-tabs__nav-item"),
    ];
    this.#contentEl = this.shadowRoot.querySelector(".ssc-tabs__content");
  }

  #bindEvents() {
    this.#tabs.forEach((tab, index) => {
      tab.addEventListener("click", () => {
        if (index === this.#activeIndex) return;
        this.#showTab(index);
      });
    });
  }

  #showTab(index) {
    const isAccordionLayout = this.getAttribute("data-layout") === "accordion";

    this.#tabs.forEach((tab, tabIndex) => {
      tab.setAttribute("aria-selected", String(tabIndex === index));
    });

    this.#panels.forEach((panel, panelIndex) => {
      const isActivePanel = panelIndex === index;

      if (isAccordionLayout) {
        panel.hidden = false;
        panel.inert = !isActivePanel;
        panel.setAttribute("data-expanded", String(isActivePanel));
        return;
      }

      panel.hidden = !isActivePanel;
      panel.removeAttribute("data-expanded");
    });

    this.#activeIndex = index;
  }

  #syncLayoutMode(useAccordionLayout) {
    if (!this.#contentEl) return;

    if (useAccordionLayout) {
      this.setAttribute("data-layout", "accordion");
    } else {
      this.removeAttribute("data-layout");
    }

    if (useAccordionLayout) {
      this.#panels.forEach((panel, index) => {
        const navItem = this.#navItems[index];
        if (!navItem) return;
        if (panel.parentElement !== navItem) {
          navItem.append(panel);
        }
      });
      return;
    }

    this.#panels.forEach((panel) => {
      if (panel.parentElement !== this.#contentEl) {
        this.#contentEl.append(panel);
      }
    });
  }

  #setupAccordionQuery() {
    this.#teardownAccordionQuery();
    this.#accordionResizeObserver = new ResizeObserver(this.#onAccordionResize);
    this.#accordionResizeObserver.observe(this);
  }

  #teardownAccordionQuery() {
    if (!this.#accordionResizeObserver) return;
    this.#accordionResizeObserver.disconnect();
    this.#accordionResizeObserver = null;
  }
}

customElements.define("ssc-tabs", SSCTabs);
