/**
 * @file
 * Overrides the default GCWeb flyout menu from WET..
 */

(function ($, wb, Drupal, once) {
  "use strict";

  Drupal.behaviors.sscMenu = {};
  Drupal.behaviors.sscMenu.attach = function (context, settings) {
    once('ssc-menu', document.documentElement, context).forEach(function () {
      // Exit if the document is in "wb disable" mode
      if (wb.isDisabled) {
        return;
      }

      // Set menu to be non-focusable on page load (closed by default)
      setMenuAsFocusable(false);

      const componentName = "ssc-menu";
      const selector = `.${componentName}`;
      const initEvent = `wb-init${selector}`;
      const selectorAjaxed =
        `${selector} [data-ajax-replace],` +
        `${selector} [data-ajax-append],` +
        `${selector} [data-ajax-prepend],` +
        `${selector} [data-wb-ajax]`;
      const menuElms = document.querySelectorAll(selector);
      let activeMenuTabIndex = 0;
      let activeMenuLinkIndex = 0;
      let isMobileMode;
      let preventFocusIn;

      // Fix aria-controls issue with GCweb menu
      $("nav.gcweb-menu a[aria-controls]").each(function () {
        $(this).next("ul").attr("id", $(this).attr("aria-controls"));
      });

      menuElms.forEach((menu) => {
        const menuBtn = menu.querySelector(".main-menu-button");
        const menuTabs = menu.querySelectorAll(
          '.main-menu-tab > [role="menuitem"]'
        );
        const menuGroups = menu.querySelectorAll(".main-menu-title-group");
        let menuGroupLinks;

        menuBtn.addEventListener("click", (event) => {
          const elm = event.currentTarget;
          let elmToGiveFocus;

          if (elm.getAttribute("aria-expanded") === "true") {
            activeMenuTabIndex = 0;
            activeMenuLinkIndex = 0;
            CloseMenu(elm, true);
            setMenuAsFocusable(false);
            return;
          }

          // Open the menu
          OpenMenu(elm);
          setMenuAsFocusable(true);

          // Focus on the first menu item
          elmToGiveFocus = elm.nextElementSibling.querySelector("[role=menuitem]");
          elmToGiveFocus.focus();
        });

        // Menu tabs event handlers
        menuTabs.forEach((tab) => {
          tab.addEventListener("focusin", (event) => {
            // Don't open the submenu on focus (mobile)
            if (isMobileMode) {
              preventFocusIn = false;
              return;
            }

            // Open the current menu
            OpenMenu(event.currentTarget);
          });

          // Open right away the popup
          tab.addEventListener("click", (event) => {
            const elm = event.currentTarget;
            let elmToGiveFocus;

            // Assign focus to the clicked tab
            activeMenuTabIndex = Number.parseInt(elm.dataset.index);
            elm.focus();

            if (isMobileMode) {
              // Don't open the submenu on focus (mobile)
              if (preventFocusIn) {
                preventFocusIn = false;
                return;
              }

              // Close all open tabs
              menuTabs.forEach((tab) => {
                if (tab !== event.target) {
                  CloseMenu(tab);
                }
              });

              // Toggle the focused tab
              if (elm.getAttribute("aria-expanded") === "true") {
                event.preventDefault();
                CloseMenu(elm, true);
                return;
              }

              // Open the menu
              OpenMenu(elm);

              // Focus on the first menu item
              elmToGiveFocus =
                elm.nextElementSibling.querySelector("[role=menuitem]");
              elmToGiveFocus.focus();
              elmToGiveFocus.setAttribute("tabindex", "0");
            }

            // Stop default behaviour
            event.stopImmediatePropagation();
            event.preventDefault();
          });
        });

        menu.addEventListener("keydown", (event) => {
          const isMenuTab =
            event.target.parentElement.classList.contains("main-menu-tab");
          const isMenuGroup = event.target.closest(".main-menu-title-group");

          if (isMenuTab) {
            event.preventDefault();
          }

          // Update list of focusable links to only contain visible ones
          if (isMenuGroup) {
            menuGroupLinks = event.target
              .closest(".main-menu-title-group")
              .querySelectorAll('[role="menuitem"]');
          }

          // Handle the "ENTER" key
          if (event.key === "Enter") {
            console.log("Press Enter key");
            if (isMenuTab) {
            console.log("Is menu tab");

              event.preventDefault();

              // Close open tabs
              menuTabs.forEach((tab) => {
                if (tab !== event.target) {
                  CloseMenu(tab);
                }
              });

              if (isMobileMode) {
                OpenMenu(event.target);
              }

              const menuGroupTitle = menuGroups[activeMenuTabIndex].querySelector(
                ".main-menu-title > a"
              );

              menuGroupTitle.focus();
            }
          }

          // Handle the "ESC" key
          if (event.key === "Escape") {
            if (isMenuTab) {
              event.preventDefault();
              activeMenuTabIndex = 0;
              activeMenuLinkIndex = 0;
              menuBtn.focus();
              CloseMenu(menuBtn, true);
              setMenuAsFocusable(false);
            } else if (isMenuGroup) {
              event.preventDefault();
              activeMenuLinkIndex = 0;
              menuTabs[activeMenuTabIndex].focus();

              // Close currently open tab on mobile
              if (isMobileMode) {
                CloseMenu(menuTabs[activeMenuTabIndex], true);
              }
            }
          }

          // Handle the "TAB" key
          if (event.key === "Tab") {
            if (isMenuTab) {
              event.preventDefault();
              if (event.shiftKey) {
                activeMenuTabIndex =
                  activeMenuTabIndex === 0
                    ? menuTabs.length - 1
                    : (activeMenuTabIndex - 1) % menuTabs.length;
              } else {
                activeMenuTabIndex = (activeMenuTabIndex + 1) % menuTabs.length;
              }

              menuTabs[activeMenuTabIndex % menuTabs.length].focus();
            } else if (isMenuGroup) {
              event.preventDefault();
              if (event.shiftKey) {
                activeMenuLinkIndex =
                  activeMenuLinkIndex === 0
                    ? menuGroupLinks.length - 1
                    : (activeMenuLinkIndex - 1) % menuGroupLinks.length;
              } else {
                activeMenuLinkIndex =
                  (activeMenuLinkIndex + 1) % menuGroupLinks.length;
              }

              menuGroupLinks[activeMenuLinkIndex % menuGroupLinks.length].focus();
            }
          }
        });
      });

      // Global hook, close the menu on "ESC" when its state are open.
      wb.doc.on("keydown", (event) => {
        if (event.key === "Escape") {
          CloseMenu(document.querySelector(selector + " button"));
        }
      });

      // Change the main menu mode
      wb.doc.on(wb.resizeEvents, (event) => {
        switch (event.type) {
          case "xxsmallview":
          case "xsmallview":
          case "smallview":
            isMobileMode = true;
            break;
          case "mediumview":
          case "largeview":
          case "xlargeview":
          default:
            isMobileMode = false;
        }
      });

      // When the menu item are ajaxed in
      wb.doc.on("ajax-fetched.wb ajax-failed.wb", selectorAjaxed, (event) => {
        let elm = event.target;

        // Filter out any events triggered by descendants
        if (event.currentTarget === elm) {
          onAjaxLoaded(elm);
        }
      });

      // Bind the init event of the plugin
      wb.doc.on("timerpoke.wb " + initEvent, selector, init);

      // Add the timer poke to initialize the plugin
      wb.add(selector);

      /**
       * @method init
       * @param {jQuery Event} event Event that triggered the function call
       */
      function init(event) {
        // Start initialization
        // returns DOM object = proceed with init
        // returns undefined = do not proceed with init (e.g., already initialized)
        const elm = wb.init(event, componentName, selector);
        let ajaxFetch;

        if (elm) {
          // If the menu item are ajaxed in, initialize after the ajax is completed
          ajaxFetch = elm.querySelector(selectorAjaxed);

          if (!ajaxFetch) {
            onAjaxLoaded(elm.querySelector("[role=menu]"));
          }
        }
      }

      function onAjaxLoaded(subElm) {
        const elm = $(subElm).parentsUntil(selector).parents();
        isMobileMode =
          document.documentElement.classList.contains("xxsmallview") ||
          document.documentElement.classList.contains("xsmallview") ||
          document.documentElement.classList.contains("smallview");

        // Identify that initialization has completed
        wb.ready(elm, componentName);
      }

      function OpenMenu(elm) {
        // If already open, do nothing
        if (elm.getAttribute("aria-expanded") === "true") {
          return;
        }

        // Close the one that is currently open for this level and deeper
        const parentMenu = elm.parentElement.parentElement;
        const menuOpen = parentMenu.querySelector(
          "[aria-haspopup][aria-expanded=true]:not([data-keep-expanded=md-min])"
        );

        // Only close other menu in tablet and desktop mode
        if (menuOpen && !isMobileMode) {
          CloseMenu(menuOpen, true);
        }

        // Open the menu
        elm.setAttribute("aria-expanded", "true");
      }

      function CloseMenu(elm, force) {
        //Ensure elm isn't null
        if (!elm) {
          return;
        }

        // Ensure elm is targeted on the haspopup element
        if (!elm.hasAttribute("aria-haspopup")) {
          elm = elm.previousElementSibling;
        }

        if (!force) {
          // Can the menu be closed?
          // Get the menu item that has the focus.
          const currentFocusIsOn = elm.nextElementSibling.querySelector(
            "[role=menuitem]:focus"
          );
          const siblingHasFocus = elm.parentElement.parentElement.querySelector(
            "[role=menuitem]:focus"
          );

          // Check if we keep the menu opon
          if (currentFocusIsOn || siblingHasFocus === elm) {
            return;
          }
        }

        // Close menu
        elm.setAttribute("aria-expanded", "false");
      }

      function setMenuAsFocusable(isFocusable) {
        if (isFocusable) {
          $('.main-menu-tabs-group').removeAttr('inert');
        } else {
          $('.main-menu-tabs-group').attr('inert', 'true');
        }
      }
    });
  };
})(jQuery, wb, Drupal, once);
