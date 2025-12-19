<div class="login frm-otp" id="wasu">
   <section>
      <div class="head">
         <h1>OTP Verify</h1>
      </div>
      <div class="body">
         <form action="" class="otp">
            <p>Enter the OTP sent to your email. Keep this code private and never share it with anyone, even if they claim to be from us.</p>
            <div class="form-otp">
               <input type="text" maxlength="1" />
               <input type="text" maxlength="1" />
               <input type="text" maxlength="1" />
               <input type="text" maxlength="1" />
               <input type="text" maxlength="1" />
               <input type="text" maxlength="1" />
            </div>

            <input id="otp_email" type="text" value="<?= $email; ?>">

            <div class="login-action">
               <button class="btn btn-primary">Verify</button>
               <span>Code not received? <a href="#">Request again</a></span>

         </form>
      </div>
   </section>
</div>


<script>
   // Autofocus otomatis ke input berikutnya
   const inputs = document.querySelectorAll(".form-otp input");
   inputs.forEach((input, index) => {
      input.addEventListener("input", () => {
         if (input.value.length === 1 && index < inputs.length - 1) {
            inputs[index + 1].focus();
         }
      });
      input.addEventListener("keydown", (e) => {
         if (e.key === "Backspace" && input.value === "" && index > 0) {
            inputs[index - 1].focus();
         }
      });
   });
</script>