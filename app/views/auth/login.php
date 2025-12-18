<div class="login">
   <section>
      <div class="head">
         <h1>Sign in</h1>
      </div>
      <div class="body">
         <form action="">
            <div class="form-group">
               <label for="isi">Nama lengkap</label>
               <div class="input-group">
                  <input type="text" name="isi" id="isi" />
               </div>
            </div>
            <div class="form-group">
               <label for="isi">Lahir</label>
               <div class="input-group">
                  <input type="text" name="isi" id="isi" />
               </div>
            </div>
            <div class="form-group">
               <label for="isi">Alamat</label>
               <div class="input-group">
                  <input type="text" name="isi" id="isi" />
               </div>
            </div>

            <div class="login-action">
               <a href="#">Forgot Password?</a>
               <button type="button" class="btn btn-primary">Sign In</button>
               <span class="sparator">or</span>

               <script src="https://accounts.google.com/gsi/client" async defer></script>

               <div class="login-google">
                  <div
                     id="g_id_onload"
                     data-client_id="598543379687-1o9bsasf8sialvhb0j8llndbhjgbgl6u.apps.googleusercontent.com"
                     data-auto_prompt="false" data-callback="googleAuth"></div>

                  <div
                     class="g_id_signin"
                     data-type="standard"
                     data-size="large"
                     data-theme="outline"
                     data-text="continue_with"
                     data-shape="pill"
                     data-logo_alignment="center"></div>
               </div>
            </div>
         </form>
      </div>
   </section>
   <span>New to CBNLink? <a href="#">Join now</a></span>
</div>