<h1><?php echo isset($item) ? 'Ubah Data' : 'Buat Data Baru'; ?></h1>

<form method="post" action="<?php echo isset($item) ? '/examplecrud/update/' . $item->id : '/examplecrud/store'; ?>">
   <label>Nama</label><br>
   <input type="text" name="name" value="<?php echo isset($item) ? htmlspecialchars($item->name) : ''; ?>" required><br><br>

   <label>Email</label><br>
   <input type="email" name="email" value="<?php echo isset($item) ? htmlspecialchars($item->email) : ''; ?>" required><br><br>

   <button type="submit"><?php echo isset($item) ? 'Update' : 'Simpan'; ?></button>
</form>

<p><a href="/examplecrud">Kembali</a></p>