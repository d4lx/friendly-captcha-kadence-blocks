(function () {
  document.addEventListener("submit", function (event) {
    const form = event.submitter ? event.submitter.closest("form") : event.target;
    if (!form) {
      return;
    }

    const element = form.querySelector(".frc-captcha");
    if (!element) {
      return;
    }

    setTimeout(function () {
      if (element.friendlyChallengeWidget) {
        element.friendlyChallengeWidget.reset();
      } else if (element.frcWidget) {
        element.frcWidget.reset();
      }
    }, 1000);
  }, true);
})();
