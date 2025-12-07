<h1>Example CRUD</h1>
<p><a href="/examplecrud/create">Buat Data Baru</a></p>

<?php if (!empty($items)): ?>
   <table border="1" cellpadding="6" cellspacing="0">
      <thead>
         <tr>
            <th>ID</th>
            <th>Nama</th>
            <th>Email</th>
            <th>Aksi</th>
         </tr>
      </thead>
      <tbody>
         <?php foreach ($items as $row): ?>
            <tr>
               <td><?php echo $row->id; ?></td>
               <td><?php echo htmlspecialchars($row->name); ?></td>
               <td><?php echo htmlspecialchars($row->email); ?></td>
               <td>
                  <a href="/examplecrud/edit/<?php echo $row->id; ?>">Edit</a> |
                  <a href="/examplecrud/delete/<?php echo $row->id; ?>" onclick="return confirm('Hapus data?')">Hapus</a>
               </td>
            </tr>
         <?php endforeach; ?>
      </tbody>
   </table>
<?php else: ?>
   <p>Tidak ada data.</p>
<?php endif; ?>