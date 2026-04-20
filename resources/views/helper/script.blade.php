<script>
    function showAuthMode(mode) {
        const regFields = document.getElementById('registration-fields');
        const authAction = document.getElementById('auth-action');
        const submitBtn = document.getElementById('submit-button');
        const tabLogin = document.getElementById('tab-login');
        const tabRegister = document.getElementById('tab-register');

        if (regFields) {
            if (mode === 'register') {
                regFields.style.display = 'block';
                if (authAction) authAction.value = 'register';
                if (submitBtn && tabRegister) submitBtn.innerText = tabRegister.innerText;
                if (tabRegister) tabRegister.classList.add('active');
                if (tabLogin) tabLogin.classList.remove('active');
                regFields.querySelectorAll('input').forEach(el => el.required = true);
            } else {
                regFields.style.display = 'none';
                if (authAction) authAction.value = 'login';
                if (submitBtn && tabLogin) submitBtn.innerText = tabLogin.innerText;
                if (tabLogin) tabLogin.classList.add('active');
                if (tabRegister) tabRegister.classList.remove('active');
                regFields.querySelectorAll('input').forEach(el => el.required = false);
            }
        }
    }
</script>
