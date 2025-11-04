
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Reports</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.3/dist/jspdf.plugin.autotable.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <style>
    body { background-color: aquamarine; font-family: Arial, sans-serif; }
    h3 { text-shadow: 1px 1px #fff; }
    .card { border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
  </style>
  </head>
  <body class="p-3">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="m-0">Transaction Reports</h3>
        <a href="logout.php" class="btn btn-outline-secondary btn-sm">Logout</a>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <form id="filter-form" class="row g-2 align-items-end">
            <div class="col-auto">
              <label class="form-label">Date start</label>
              <input type="date" class="form-control" id="date_start">
            </div>
            <div class="col-auto">
              <label class="form-label">Date end</label>
              <input type="date" class="form-control" id="date_end">
            </div>
            <div class="col-auto">
              <button class="btn btn-primary" type="submit">Filter</button>
            </div>
            <div class="col-auto ms-auto">
              <button id="btn-pdf" class="btn btn-outline-dark" type="button">Print PDF</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="table-responsive">
          <table class="table table-striped table-hover m-0">
            <thead>
              <tr>
                <th>#</th>
                <th>Date</th>
                <th></th>
                <th>Received By</th>
                <th>Received At</th>
                <th>Total (₱)</th>
                <th>Paid (₱)</th>
                <th>Change (₱)</th>
              </tr>
            </thead>
            <tbody id="tbody-reports"></tbody>
            <tfoot>
              <tr>
                <th colspan="3" class="text-end">Grand Total:</th>
                <th id="tfoot-sum">₱0</th>
                <th></th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <script>
      async function loadReport() {
        const fd = new FormData();
        fd.append('action', 'report');
        const ds = document.getElementById('date_start').value;
        const de = document.getElementById('date_end').value;
        if (ds) fd.append('date_start', ds);
        if (de) fd.append('date_end', de);
        const res = await fetch('api/handler.php', { method: 'POST', body: fd }).then(r => r.json());
        if (!res.success) {
          await Swal.fire('Error', res.message || 'Failed to load report', 'error');
          return;
        }
        const tbody = document.getElementById('tbody-reports');
        tbody.innerHTML = '';
        (res.orders || []).forEach((o, idx) => {
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${idx + 1}</td>
            <td>${o.date_added}</td>
            <td>${o.cashier || '-'}</td>
            <td>${o.received_by_name || '-'}</td>
            <td>${o.received_at || '-'}</td>
            <td>₱${Number(o.total).toFixed(2)}</td>
            <td>₱${o.payment_amount !== null ? Number(o.payment_amount).toFixed(2) : '-'}</td>
            <td>₱${o.change_amount !== null ? Number(o.change_amount).toFixed(2) : '-'}</td>
          `;
          tbody.appendChild(tr);
        });
        document.getElementById('tfoot-sum').innerText = '₱' + Number(res.total_sum || 0).toFixed(2);
      }

      document.getElementById('filter-form').addEventListener('submit', function(e){
        e.preventDefault();
        loadReport();
      });

      document.getElementById('btn-pdf').addEventListener('click', async function(){
        // Generate PDF client-side using current filter
        const fd = new FormData();
        fd.append('action', 'report');
        const ds = document.getElementById('date_start').value;
        const de = document.getElementById('date_end').value;
        if (ds) fd.append('date_start', ds);
        if (de) fd.append('date_end', de);
        const res = await fetch('api/handler.php', { method: 'POST', body: fd }).then(r => r.json());
        if (!res.success) {
          await Swal.fire('Error', res.message || 'Unable to load data for PDF', 'error');
          return;
        }
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: 'portrait', unit: 'pt', format: 'a4' });
        const title = 'Transaction Report';
        doc.setFontSize(16);
        doc.text(title, 40, 40);
        let subtitle = 'All Dates';
        if (ds || de) subtitle = `From ${ds || '...'} to ${de || '...'}`;
        doc.setFontSize(10);
        doc.text(subtitle, 40, 58);

        const rows = (res.orders || []).map((o, i) => [
          String(i + 1),
          o.date_added,
          o.cashier || '-',
          o.received_by_name || '-',
          o.received_at || '-',
          Number(o.total).toFixed(2),
          o.payment_amount !== null ? Number(o.payment_amount).toFixed(2) : '-',
          o.change_amount !== null ? Number(o.change_amount).toFixed(2) : '-',
        ]);

        doc.autoTable({
          startY: 80,
          head: [["#","Date","Cashier","Received By","Received At","Total (₱)","Paid (₱)","Change (₱)"]],
          body: rows,
          styles: { fontSize: 9 },
          headStyles: { fillColor: [33, 150, 243] }
        });

        const finalY = doc.lastAutoTable.finalY || 80;
        doc.setFontSize(12);
        doc.text(`Grand Total: ₱${Number(res.total_sum || 0).toFixed(2)}`, 40, finalY + 24);
        doc.save(`report_${ds || 'all'}_${de || 'all'}.pdf`);
      });

      loadReport();
    </script>
  </body>
</html>
