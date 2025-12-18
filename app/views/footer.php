   <footer>
      <p>&copy; 2025 PT.SUN Solutions Corp. All rights reserved.</p>
   </footer>
   </body>

   <!-- 
<script>
   const observer = new MutationObserver((mutations) => {
      for (const m of mutations) {
         if (m.type !== "childList") continue;
         m.addedNodes.forEach((node) => {
            if (node.nodeType !== 1) return; // hanya elemen
            node.querySelectorAll(".form-group input").forEach((input) => {
               const formGroup = input.closest(".form-group");
               if (!formGroup) return;
               // hindari double event listener
               if (q.helper.expando.get(input, "obs")) {
                  return;
               }
               q.helper.expando.set(input, "obs", true);

               // set awal jika input sudah ada value
               if (input.value.trim() !== "") {
                  formGroup.classList.add("focused");
               }
               // event focus / blur
               input.addEventListener("focus", () => {
                  formGroup.classList.add("focused");
               });
               input.addEventListener("blur", () => {
                  if (input.value.trim() === "") {
                     formGroup.classList.remove("focused");
                  }
               });
            });
         });
      }
   });
</script> -->
   <script>
      const populationData = {
         labels: ["0-17 tahun", "18-35 tahun", "36-55 tahun", "56+ tahun"],
         datasets: [{
            label: "Jumlah Penduduk",
            data: [3125, 4375, 3125, 1875],
            backgroundColor: ["#6366f1", "#10b981", "#f59e0b", "#ef4444"],
            borderWidth: 0,
         }, ],
      };

      const budgetData = {
         labels: ["Pemasukan", "Pengeluaran"],
         datasets: [{
            label: "Anggaran (Rp Juta)",
            data: [2500, 2250],
            backgroundColor: ["#10b981", "#ef4444"],
            borderWidth: 0,
         }, ],
      };

      const dataPBB = {
         labels: ["2020", "2021", "2022", "2023", "2024"],
         datasets: [{
            label: "Pajak PBB (Rp Juta)",
            data: [50, 55, 60, 58, 62],
            borderColor: "#9b59b6",
            fill: false,
         }, ],
      };

      const dataAparatur = {
         labels: ["Kepala Desa", "Sekretaris", "Kaur", "Kadus", "BPD"],
         datasets: [{
            label: "Jumlah Aparatur",
            data: [1, 1, 3, 5, 7],
            backgroundColor: "#34495e",
         }, ],
      };

      const chartOptions = {
         responsive: true,
         maintainAspectRatio: false,
         plugins: {
            legend: {
               display: false,
            },
         },
         scales: {
            y: {
               beginAtZero: true,
               grid: {
                  color: "rgba(255,255,255,0.1)",
               },
               ticks: {
                  color: "#a3a8ad",
               },
            },
            x: {
               grid: {
                  color: "rgba(255,255,255,0.1)",
               },
               ticks: {
                  color: "#a3a8ad",
               },
            },
         },
      };

      new Chart(document.getElementById("populationChart"), {
         type: "doughnut",
         data: populationData,
         options: {
            ...chartOptions,
            plugins: {
               legend: {
                  position: "bottom",
                  labels: {
                     color: "#a3a8ad",
                     padding: 20,
                     usePointStyle: true,
                  },
               },
            },
         },
      });

      new Chart(document.getElementById("budgetChart"), {
         type: "bar",
         data: budgetData,
         options: chartOptions,
      });

      new Chart(document.getElementById("chartPBB"), {
         type: "line",
         data: dataPBB,
         options: chartOptions,
      });

      new Chart(document.getElementById("chartAparatur"), {
         type: "bar",
         data: dataAparatur,
         options: chartOptions,
      });
   </script>