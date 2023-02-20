(function() {
  document.addEventListener("click", function(e) {
    if (e.target.matches(".notice.is-dismissible[data-dismissible-id] button.notice-dismiss")) {
      let noticeId = e.target.closest(".notice.is-dismissible[data-dismissible-id]").dataset.dismissibleId;
      document.cookie = "dismissed_notice_" + noticeId + "=1;path=/;max-age=" + (86400 * 30) + "; secure";
    }
  }, true);
})();

var loadDismissibleNotices = function() {
  var $ = jQuery;

  $(".notice.is-dismissible").each(function() {
    var b = $(this)
      , c = $('<button type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button>');
      c.on("click.wp-dismiss-notice", function($) {
        $.preventDefault(),
          b.fadeTo(100, 0, function() {
            b.slideUp(100, function() {
              b.remove()
            })
          })
      }),
      b.append(c)
  });
}
