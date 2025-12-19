   <section>
      <div class="head">
         <h1 class="center">Data & Statistik Desa</h1>
      </div>
      <div class="body">
         <!-- Charts Section -->
         <div class="card">
            <h3>Distribusi Penduduk</h3>
            <canvas id="populationChart"></canvas>
         </div>
         <div class="card">
            <h3>Anggaran Desa</h3>
            <canvas id="budgetChart"></canvas>
         </div>
         <div class="card">
            <h3>Pajak PBB</h3>
            <canvas id="chartPBB"></canvas>
         </div>

         <div class="card">
            <h3>Aparatur</h3>
            <canvas id="chartAparatur"></canvas>
         </div>
      </div>
   </section>

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