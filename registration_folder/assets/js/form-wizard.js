/**
 * Form Wizard Logic
 */
document.addEventListener('DOMContentLoaded', function () {
    const steps = document.querySelectorAll('.step-pane');
    const stepIndicators = document.querySelectorAll('.step-item');
    const btnNext = document.querySelector('.btn-next');
    const btnPrev = document.querySelector('.btn-prev');
    const btnSubmit = document.querySelector('.btn-submit');
    let currentStep = 0;

    function showStep(index) {
        // Validation logic for previous step could go here

        // Hide all steps
        steps.forEach((step, i) => {
            step.classList.remove('active');
            stepIndicators[i].classList.remove('active');
            if (i < index) {
                stepIndicators[i].classList.add('completed');
            } else {
                stepIndicators[i].classList.remove('completed');
            }
        });

        // Show current
        steps[index].classList.add('active');
        stepIndicators[index].classList.add('active');

        // Update Buttons
        if (index === 0) {
            btnPrev.style.display = 'none';
        } else {
            btnPrev.style.display = 'inline-flex';
        }

        if (index === steps.length - 1) {
            btnNext.style.display = 'none';
            btnSubmit.style.display = 'inline-flex';
        } else {
            btnNext.style.display = 'inline-flex';
            btnSubmit.style.display = 'none';
        }
    }

    // Init
    showStep(currentStep);

    // Input Cleanup Listener (Delegated)
    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('is-invalid')) {
            e.target.classList.remove('is-invalid');
        }
    });

    // Event Listeners
    btnNext.addEventListener('click', function () {
        // Validate inputs in current step
        const currentInputs = steps[currentStep].querySelectorAll('input[required], select[required]');
        let valid = true;
        let firstInvalid = null;

        currentInputs.forEach(input => {
            if (!input.value.trim()) {
                valid = false;
                input.classList.add('is-invalid');
                if (!firstInvalid) firstInvalid = input;
            } else {
                input.classList.remove('is-invalid');
            }
        });

        if (valid) {
            currentStep++;
            showStep(currentStep);
        } else {
            console.error('Validation failed for fields:', Array.from(steps[currentStep].querySelectorAll('.is-invalid')).map(el => el.id || el.name));
            // Optional: Show a subtle hint if the button is clicked and fails
            if (firstInvalid) {
                firstInvalid.focus();
                // If it's the first step and everything looks filled, maybe a hidden field is required?
                // Let's alert the user what's missing.
                const labels = Array.from(steps[currentStep].querySelectorAll('.is-invalid')).map(el => {
                    const label = el.closest('.form-group')?.querySelector('label');
                    return label ? label.textContent.replace('*', '').trim() : (el.id || el.name);
                });
                alert('Please fill in or correct the following required fields: \n- ' + labels.join('\n- '));
            }
        }
    });

    btnPrev.addEventListener('click', function () {
        currentStep--;
        showStep(currentStep);
    });

});
