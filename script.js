document.addEventListener('DOMContentLoaded', () => {


  // =====================================================
  // CURRENT YEAR
  // =====================================================

  const year = document.getElementById('year');

  if (year) {
    year.textContent = new Date().getFullYear();
  }



  // =====================================================
  // MOBILE NAVIGATION
  // =====================================================

  const menuToggle =
    document.querySelector('.menu-toggle');

  const navigation =
    document.querySelector('.site-header nav');


  if (menuToggle && navigation) {

    menuToggle.addEventListener(
      'click',
      () => {

        const isOpen =
          navigation.classList.toggle('open');

        menuToggle.setAttribute(
          'aria-expanded',
          String(isOpen)
        );

      }
    );


    /*
      Close mobile navigation after
      a navigation link is selected.
    */

    navigation
      .querySelectorAll('a')
      .forEach((link) => {

        link.addEventListener(
          'click',
          () => {

            navigation.classList.remove('open');

            menuToggle.setAttribute(
              'aria-expanded',
              'false'
            );

          }
        );

      });

  }



  // =====================================================
  // VOLUNTEER FORM
  // =====================================================

  const form =
    document.getElementById('signup-form');


  if (!form) {
    return;
  }


  const message =
    form.querySelector('.form-message');

  const submitButton =
    form.querySelector(
      'button[type="submit"]'
    );


  form.addEventListener(
    'submit',
    async (event) => {

      event.preventDefault();


      /*
        Let the browser check required
        fields and email formatting first.
      */

      if (!form.checkValidity()) {

        form.reportValidity();

        return;
      }


      const originalButtonText =
        submitButton.textContent;


      submitButton.disabled = true;

      submitButton.textContent =
        'SENDING...';


      if (message) {

        message.textContent = '';

        message.classList.remove(
          'success',
          'error'
        );

      }


      try {

        const formData =
          new FormData(form);


        const response =
          await fetch(
            'submit-volunteer.php',
            {
              method: 'POST',
              body: formData
            }
          );


        /*
          Read as text first so we can
          handle unexpected PHP/server
          responses more safely.
        */

        const responseText =
          await response.text();


        let result;


        try {

          result =
            JSON.parse(responseText);

        } catch (jsonError) {

          console.error(
            'Unexpected server response:',
            responseText
          );

          throw new Error(
            'The server returned an unexpected response.'
          );

        }


        if (
          !response.ok ||
          !result.success
        ) {

          throw new Error(
            result.message ||
            'Unable to submit the form.'
          );

        }


        /*
          Successful submission
        */

        if (message) {

          message.textContent =
            'Thank you for volunteering! We’ll be in touch soon.';

          message.classList.add(
            'success'
          );

        }


        form.reset();


      } catch (error) {

        console.error(
          'Volunteer form error:',
          error
        );


        if (message) {

          message.textContent =
            error.message ||
            'Something went wrong. Please try again or email info@vote4arslan.ca.';

          message.classList.add(
            'error'
          );

        }

      } finally {

        submitButton.disabled = false;

        submitButton.textContent =
          originalButtonText;

      }

    }
  );

});