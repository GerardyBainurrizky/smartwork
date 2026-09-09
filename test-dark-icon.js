import fs from 'fs';

const html = `<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
/* Light mode */
html { color-scheme: light; }
.card { background: white; padding: 20px; margin: 10px; border: 1px solid #ccc; width: 350px; }
input[type="date"] {
  display: block;
  width: 100%;
  height: 48px;
  padding: 0 16px;
  box-sizing: border-box;
  font-size: 14px;
}

/* Dark mode version */
.dark-container {
  background: #111827;
  padding: 20px;
}
.dark-container .card {
  background: #1f2937;
  border-color: #374151;
}
.dark-container input[type="date"] {
  background: #1f2937;
  color: #e5e7eb;
  border: 1px solid #374151;
}

/* Method 1: filter invert */
.m1 input[type="date"]::-webkit-calendar-picker-indicator {
  filter: invert(1);
}

/* Method 2: filter invert + brightness */
.m2 input[type="date"]::-webkit-calendar-picker-indicator {
  filter: invert(1) brightness(1.2) contrast(1.1);
}

/* Method 3: color-scheme: dark on input */
.m3 input[type="date"] {
  color-scheme: dark;
}

/* Method 4: color-scheme: dark + filter */
.m4 input[type="date"] {
  color-scheme: dark;
}
.m4 input[type="date"]::-webkit-calendar-picker-indicator {
  filter: invert(1);
}

/* Method 5: SVG mask / background-image */
.m5 input[type="date"]::-webkit-calendar-picker-indicator {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23ffffff' viewBox='0 0 24 24'%3E%3Cpath d='M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z'/%3E%3C/svg%3E");
}
</style>
</head>
<body>
  <div class="card">
    <h3>Light Mode Normal</h3>
    <input type="date" value="2026-09-07">
  </div>

  <div class="dark-container">
    <div class="card m1">
      <h3 style="color:white">Dark M1: filter: invert(1)</h3>
      <input type="date" value="2026-09-07">
    </div>
    <div class="card m2">
      <h3 style="color:white">Dark M2: filter: invert(1) brightness(1.2)</h3>
      <input type="date" value="2026-09-07">
    </div>
    <div class="card m3">
      <h3 style="color:white">Dark M3: color-scheme: dark</h3>
      <input type="date" value="2026-09-07">
    </div>
    <div class="card m4">
      <h3 style="color:white">Dark M4: color-scheme: dark + invert(1)</h3>
      <input type="date" value="2026-09-07">
    </div>
  </div>
</body>
</html>`;

fs.writeFileSync('test-dark-icon.html', html);
