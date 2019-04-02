/**
 * @file
 * Adds JavaScript functionality to priveate message threads.
 */

/*global jQuery, Drupal, drupalSettings, window*/
/*jslint white:true, this, browser:true*/

Drupal.PrivateMessages = {};
Drupal.PrivateMessages.threadChange = {};

(function ($, Drupal, drupalSettings, window) {

  "use strict";

  var initialized, threadWrapper, currentThreadId, originalThreadId, loadingPrev, loadingNew, container, timeout, refreshRate, dimmer, loadingThread;

  /**
   * Triggers AJAX commands when they happen outside the Form API framework.
   */
  function triggerCommands(data) {
    var ajaxObject = Drupal.ajax({
      url: "",
      base: false,
      element: false,
      progress: false
    });

    // Trigger any any ajax commands in the response.
    ajaxObject.success(data, "success");
  }

  function showDimmer(callback) {
    if (!dimmer) {
      dimmer = $("<div/>", {id:"private-message-thread-dimmer"}).appendTo(threadWrapper);
    }

    dimmer.fadeTo(500, 0.8, callback);
  }

  function hideDimmer() {
    if (dimmer) {
      dimmer.fadeOut(500);
    }
  }

  /**
   * Click handler for the button to load previous private messages.
   */
  var loadPreviousListenerHandler = function (e) {
    e.preventDefault();

    // Ensure that a load isn't already in progress.
    if (!loadingPrev) {
      loadingPrev = true;

      var threadId, oldestId;

      // Get the thread ID.
      threadId = threadWrapper.children(".private-message-thread:first").attr("data-thread-id");

      // Get the ID of the oldest message. This will be used for reference to
      // tell the server which messages it should send back.
      container.find(".private-message").each(function () {
        if (!oldestId || Number($(this).attr("data-message-id")) < oldestId) {
          oldestId = Number($(this).attr("data-message-id"));
        }
      });

      // Retrieve messages from the server with an AJAX callback.
      $.ajax({
        url:drupalSettings.privateMessageThread.previousMessageCheckUrl,
        data: {threadid:threadId, messageid:oldestId},
        success:function (data) {
          loadingPrev = false;
          // Trigger the AJAX commands that were returned from the server.
          triggerCommands(data);
        }
      });
    }
  };

  /**
   * Attaches event handlers to the load previous messages button.
   */
  function loadPreviousListener(context) {
    $(context).find("#load-previous-messages").once("load-previous-private-messages-listener").each(function () {
      $(this).click(loadPreviousListenerHandler);
    });
  }

  /**
   * Inserts new messages into the thread.
   */
  function insertNewMessages(messages) {
    // Render the messages as HTML, and set them to be hidden.
    var html = $("<div/>").html(messages).contents().css("display", "none");

    // Insert the messages into the thread.
    if (drupalSettings.privateMessageThread.messageOrder === "asc") {
      html.appendTo(container);
    }
    else {
      html.prependTo(container);
    }

    // Show the messages.
    html.slideDown(300);

    Drupal.attachBehaviors(html[0]);
  }

  // Insert older messages into the thread.
  function insertPreviousMessages(messages) {
    // Render the messages as HTML, setting them to be hidden.
    var html = $("<div/>").html(messages).contents().css("display", "none");

    // Insert the messages into the thread.
    if (drupalSettings.privateMessageThread.messageOrder === "asc") {
      html.prependTo(container);
    }
    else {
      html.appendTo(container);
    }

    // Show the messages.
    html.slideDown(300);

    Drupal.attachBehaviors(html[0]);
  }

  /**
   * Retrieves new messages from the server.
   */
  function getNewMessages() {
    // Only attempt a retrieval if one is not already in progress.
    if (!loadingNew) {
      var threadId, newestId = 0;

      loadingNew = true;

      // Get the thread ID.
      threadId = threadWrapper.children(".private-message-thread:first").attr("data-thread-id");

      // Get the ID of the newest message. This will be used as a reference
      // server side to determine which messages to return to the browser.
      container.find(".private-message").each(function () {
        if (Number($(this).attr("data-message-id")) > newestId) {
          newestId = Number($(this).attr("data-message-id"));
        }
      });

      $.ajax({
        url:drupalSettings.privateMessageThread.newMessageCheckUrl,
        data: {threadid:threadId, messageid:newestId},
        success:function (data) {
          triggerCommands(data);

          loadingNew = false;

          if (refreshRate) {
             // Check for new messages again.
            timeout = window.setTimeout(getNewMessages, refreshRate);
          }
        }
      });
    }
  }

  /**
   * Remove the existing thread from the DOM, and insert a new one in its place.
   */
  function insertThread(thread) {
    var newThread, originalThread;

    // Render the new thread as HTML.
    newThread = $("<div/>").html(thread).find(".private-message-thread:first");
    // Find the current thread in the DOM.
    originalThread = threadWrapper.children(".private-message-thread:first");
    // Detach any behaviors from the old thread, to prevent memory leaks.
    Drupal.detachBehaviors(threadWrapper[0]);
    // Insert the new thread into the DOM.
    newThread.insertAfter(originalThread);
    // Remove the old thread from teh DOM.
    originalThread.remove();

    // Attach any behaviors to the new thread.
    Drupal.attachBehaviors(threadWrapper[0]);

    hideDimmer();
  }

  /**
   * Loads a thread from the server.
   */
  function loadThread(threadId, pushHistory) {
    // Only try loading the thread if a thread isn't already loading, and if the
    // requested thread is not the current thread.
    if (!loadingThread && threadId !== currentThreadId) {
      loadingThread = true;

      window.clearTimeout(timeout);

      showDimmer();

      // Load the new thread from the server with AJAX.
      $.ajax({
        url:drupalSettings.privateMessageThread.loadThreadUrl,
        data:{id:threadId},
        success:function (data) {
          triggerCommands(data);

          if (Drupal.PrivateMessages.setActiveThread) {
            Drupal.PrivateMessages.setActiveThread(threadId);
          }

          loadingThread = false;

          timeout = window.setTimeout(getNewMessages, refreshRate);
        }
      });

      // The thread ID is changing. As such, we tell any other scripts that want
      // to know, that the thread has changed, and what the new thread ID is.
      Drupal.PrivateMessages.emitNewThreadId(threadId);

      // Change the URl if using a browser that allows it.
      if (pushHistory) {
        Drupal.history.push({threadId:threadId}, $("title").text(), "/private_messages/" + threadId);
      }
    }
  }

  function init() {
    if (!initialized) {
      initialized = true;

      // Initialize the previous button. This will be inserted into the thread.
      var loadPreviousButton;

      // Find the wrapper for the current thread.
      threadWrapper = $(".private-message-thread").parent();
      // Get the rate (in seconds) after which the server should be polled for
      // new messages.
      refreshRate = drupalSettings.privateMessageThread.refreshRate;
      // Get the container for messages.
      container = threadWrapper.find(".private-message-thread-messages:first .private-message-wrapper:first").parent();

      // Create the HTML for the load previous button.
      loadPreviousButton = $("<div/>", {id:"load-previous-messages-button-wrapper"}).append($("<a/>", {href:"#", id:"load-previous-messages"}).text(Drupal.t("Load Previous")));

      // Insert the load previous button into the DOM.
      if (drupalSettings.privateMessageThread.messageOrder === "asc") {
        loadPreviousButton.addClass("load-previous-position-before").insertBefore(container);
      }
      else {
        loadPreviousButton.addClass("load-previous-position-after").insertAfter(container);
      }

      // Get the original thread ID on page load.
      originalThreadId = threadWrapper.children(".private-message-thread:first").attr("data-thread-id");

      // If the refresh rate is anything above zero (zero is disabled) start the
      // server polling for new messages.
      if (refreshRate) {
        timeout = window.setTimeout(getNewMessages, refreshRate);
      }

      // Set the active thread.
      if (Drupal.PrivateMessages.setActiveThread) {
        Drupal.PrivateMessages.setActiveThread(originalThreadId);
      }
    }
  }

  Drupal.behaviors.privateMessageThread = {
    attach:function (context) {
      init();
      loadPreviousListener(context);
      currentThreadId = threadWrapper.children(".private-message-thread:first").attr("data-thread-id");
      container = threadWrapper.find(".private-message-thread-messages:first .private-message-wrapper:first").parent();

      // Ajax commands insertPrivateMessages command callback.
      Drupal.AjaxCommands.prototype.insertPrivateMessages = function (ajax, response) {
        // Stifles jSlint warning.
        ajax = ajax;

        if (response.insertType === "new") {
          insertNewMessages(response.messages);
        }
        else {
          if (response.messages) {
            insertPreviousMessages(response.messages);
          }
          else {
            $("#load-previous-messages").parent().slideUp(300, function () {
              $(this).remove();
            });
          }
        }
      };

      // Ajax commands loadNewPrivateMessages command callback.
      Drupal.AjaxCommands.prototype.loadNewPrivateMessages = function () {

        window.clearTimeout(timeout);

        getNewMessages();
      };

      // Ajax commands privateMessageInsertThread command callback.
      Drupal.AjaxCommands.prototype.privateMessageInsertThread = function (ajax, response) {
        // Stifle jslint warning.
        ajax = ajax;

        if (response.thread && response.thread.length) {
          insertThread(response.thread);
        }
      };

      // Lets other modules trigger the loading of a new thread into the page.
      Drupal.PrivateMessages.loadThread = function (threadId) {
        loadThread(threadId, true);
      };

      // Lets other modules trigger a retrieval of new messages from the server.
      Drupal.PrivateMessages.getNewMessages = function () {
        getNewMessages();
      };

      // Tells other modules the ID of a new thread that has been inserted into
      // the page.
      Drupal.PrivateMessages.emitNewThreadId = function (threadId) {
        $.each(Drupal.PrivateMessages.threadChange, function (index) {
          if (Drupal.PrivateMessages.threadChange[index].threadLoaded) {
            Drupal.PrivateMessages.threadChange[index].threadLoaded(threadId);
          }
        });
      };
    },
    detach:function (context) {
      $(context).find("#load-previous-messages").unbind("click", loadPreviousListenerHandler);
    }
  };

  // Integrates the script with the previous/next buttons in the browser.
  window.onpopstate = function (e) {
    if (e.state && e.state.threadId) {
      loadThread(e.state.threadId);
    }
    else {
      loadThread(originalThreadId);
    }
  };

}(jQuery, Drupal, drupalSettings, window));
