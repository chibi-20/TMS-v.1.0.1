document.addEventListener('DOMContentLoaded', () => {
  const teacherList = document.getElementById('teacherList');
  const addBtn = document.getElementById('addTeacherBtn');
  const tabs = document.querySelectorAll('.tab');
  const tabContents = document.querySelectorAll('.tab-content');
  const sortSelect = document.getElementById('sortSelect');
  const schoolYearSelect = document.getElementById('schoolYearSelect');
  const teacherTableBody = document.querySelector('#teacherTable tbody');
  const teacherSearch = document.getElementById('teacherSearch');
  let teachersData = []; // Store fetched data globally
  let positionChart, yearsChart, ipcrfChart;

  // Fix: Add Teacher button working
  if (addBtn) {
    addBtn.addEventListener('click', () => {
      window.location.href = 'register.html';
    });
  }

  // Tab switching
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      tabContents.forEach(c => c.classList.remove('active'));
      tab.classList.add('active');
      document.getElementById(tab.dataset.tab).classList.add('active');
    });
  });

  // Fetch teacher data
  fetch('fetch_teachers.php')
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        teacherList.textContent = 'Error loading teachers: ' + (data.message || 'Unknown error');
        return;
      }

      let teachers = data.data;
      if (!Array.isArray(teachers) || teachers.length === 0) {
        teacherList.textContent = 'No teachers found.';
        return;
      }

      // SORTING LOGIC
      teachers.sort((a, b) => {
        // Sort by school_year (descending)
        if (a.school_year !== b.school_year) {
          return b.school_year.localeCompare(a.school_year);
        }
        // Then by name (ascending)
        return a.full_name.localeCompare(b.full_name);
      });

      teachersData = teachers; // Save globally
      renderTeachers(sortTeachers(teachersData, sortSelect.value));
      renderTeacherTable(teachersData); // Initial render for table
      renderCharts(teachersData); // Initial render for charts
      renderSummaryTables(teachersData);
    })
    .catch(err => {
      console.error('Fetch error:', err);
      teacherList.textContent = 'Failed to fetch data.';
    });

  function renderTeachers(teachers) {
    teacherList.innerHTML = '';
    teachers.forEach((teacher, index) => {
      const educationByType = { bachelor: [], master: [], doctoral: [] };
      if (Array.isArray(teacher.education)) {
        teacher.education.forEach(e => {
          if (educationByType[e.type]) educationByType[e.type].push(e);
        });
      }

      // Only show degree sections if they exist
      let educationHTML = '';
      ['bachelor', 'master', 'doctoral'].forEach(level => {
        const eduList = educationByType[level];
        if (eduList.length > 0) {
          educationHTML += `<li><strong>${capitalize(level)}:</strong>
            <ul>${eduList.map(e =>
              `<li>${e.degree} in ${e.major} from ${e.school} (${e.year_attended})
                ${e.status ? ` - <em>${e.status}</em>` : ''}
                ${e.details ? ` - <u>${e.details}</u>` : ''}
              </li>`).join('')}
            </ul></li>`;
        }
      });
      if (!educationHTML) educationHTML = '<li>No educational data.</li>';

      const trainingsHTML = Array.isArray(teacher.trainings) && teacher.trainings.length > 0
        ? teacher.trainings.map(t => `<li>${t.title} (${t.date} - ${t.level})</li>`).join('')
        : '<li>No training data.</li>';

      const card = document.createElement('div');
      card.className = 'teacher-card';
      card.innerHTML = `
        <div class="teacher-header">
          <div>${teacher.full_name}</div>
          <div>${teacher.position}</div>
          <div>${teacher.years_in_teaching}</div>
          <div>${teacher.ipcrf_rating}</div>
          <div>${teacher.school_year}</div>
          <div><button class="toggle-btn" data-id="${index}">Show</button></div>
        </div>
        <div class="teacher-details" id="details-${index}">
          <h4>📚 Trainings Attended</h4>
          <ul>${trainingsHTML}</ul>
          <h4>🎓 Educational Attainment</h4>
          <ul>${educationHTML}</ul>
        </div>
      `;
      teacherList.appendChild(card);
    });

    document.querySelectorAll('.toggle-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const detailEl = document.getElementById(`details-${id}`);
        const visible = detailEl.style.display === 'block';
        detailEl.style.display = visible ? 'none' : 'block';
        btn.textContent = visible ? 'Show' : 'Hide';
      });
    });
  }

  function renderTeacherTable(filteredTeachers) {
    teacherTableBody.innerHTML = '';
    filteredTeachers.forEach(teacher => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${teacher.full_name}</td>
        <td>${teacher.position}</td>
        <td>${teacher.years_in_teaching}</td>
        <td>${teacher.ipcrf_rating}</td>
        <td>${teacher.school_year}</td>
        <td>${Array.isArray(teacher.trainings) ? teacher.trainings.map(t => t.title).join(', ') : ''}</td>
        <td>${Array.isArray(teacher.education) ? teacher.education.map(e => e.degree).join(', ') : ''}</td>
      `;
      teacherTableBody.appendChild(tr);
    });
  }

  // Filter and render on school year change
  schoolYearSelect.addEventListener('change', () => {
    const selectedYear = schoolYearSelect.value;
    const filtered = teachersData.filter(t => t.school_year === selectedYear);
    renderTeacherTable(filtered); // updates the table
    renderTeachers(sortTeachers(filtered, sortSelect.value)); // updates the card view
    renderCharts(filtered); // updates the graphs with filtered data
    renderSummaryTables(filtered);
  });

  teacherSearch.addEventListener('input', () => {
    const query = teacherSearch.value.trim().toLowerCase();
    const selectedYear = schoolYearSelect.value;
    let filtered = teachersData.filter(t => t.school_year === selectedYear);

    if (query) {
      filtered = filtered.filter(t =>
        t.full_name.toLowerCase().includes(query) ||
        t.position.toLowerCase().includes(query) ||
        String(t.years_in_teaching).includes(query) ||
        String(t.ipcrf_rating).includes(query)
      );
    }

    renderTeacherTable(filtered);
    renderTeachers(sortTeachers(filtered, sortSelect.value));
    renderCharts(filtered);
    renderSummaryTables(filtered);
  });

  function sortTeachers(teachers, criteria) {
    return teachers.slice().sort((a, b) => {
      if (criteria === 'years_in_teaching') {
        return b.years_in_teaching - a.years_in_teaching;
      }
      if (criteria === 'position') {
        return a.position.localeCompare(b.position);
      }
      if (criteria === 'ipcrf_rating') {
        return parseFloat(b.ipcrf_rating) - parseFloat(a.ipcrf_rating);
      }
      // Default: school_year
      return b.school_year.localeCompare(a.school_year);
    });
  }

  sortSelect.addEventListener('change', () => {
    renderTeachers(sortTeachers(teachersData, sortSelect.value));
  });

  function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  function renderCharts(teachers) {
    // Destroy previous charts if they exist
    if (positionChart) positionChart.destroy();
    if (yearsChart) yearsChart.destroy();
    if (ipcrfChart) ipcrfChart.destroy();

    // --- Position Bar Graph ---
    const positionCounts = {};
    teachers.forEach(t => {
      positionCounts[t.position] = (positionCounts[t.position] || 0) + 1;
    });
    const positionLabels = Object.keys(positionCounts);
    const positionData = Object.values(positionCounts);

    positionChart = new Chart(document.getElementById('positionBarChart'), {
      type: 'bar',
      data: {
        labels: positionLabels,
        datasets: [{
          label: 'Number of Teachers',
          data: positionData,
          backgroundColor: 'rgba(54, 162, 235, 0.6)'
        }]
      }
    });

    // --- Years in Service Pie Graph ---
    // Group years into ranges (e.g., 0-5, 6-10, 11-15, etc.)
    const yearRanges = ['0-5', '6-10', '11-15', '16-20', '21+'];
    const yearCounts = [0, 0, 0, 0, 0];
    teachers.forEach(t => {
      const y = Number(t.years_in_teaching);
      if (y <= 5) yearCounts[0]++;
      else if (y <= 10) yearCounts[1]++;
      else if (y <= 15) yearCounts[2]++;
      else if (y <= 20) yearCounts[3]++;
      else yearCounts[4]++;
    });

    yearsChart = new Chart(document.getElementById('yearsPieChart'), {
      type: 'pie',
      data: {
        labels: yearRanges,
        datasets: [{
          data: yearCounts,
          backgroundColor: [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'
          ]
        }]
      }
    });

    // --- IPCRF Rating Pie Graph ---
    // Group by rating value (e.g., 1, 2, 3, 4, 5)
    const ipcrfLabels = ['1', '2', '3', '4', '5'];
    const ipcrfCounts = [0, 0, 0, 0, 0];
    teachers.forEach(t => {
      const r = Math.round(Number(t.ipcrf_rating));
      if (r >= 1 && r <= 5) ipcrfCounts[r - 1]++;
    });

    ipcrfChart = new Chart(document.getElementById('ipcrfPieChart'), {
      type: 'pie',
      data: {
        labels: ipcrfLabels,
        datasets: [{
          data: ipcrfCounts,
          backgroundColor: [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'
          ]
        }]
      }
    });
  }

  function renderSummaryTables(teachers) {
    // --- 1. Number of teachers per position ---
    const positionCounts = {};
    teachers.forEach(t => {
      positionCounts[t.position] = (positionCounts[t.position] || 0) + 1;
    });
    let posTable = `<table border="1"><thead><tr><th>Position</th><th>Number of Teachers</th></tr></thead><tbody>`;
    Object.entries(positionCounts).forEach(([pos, count]) => {
      posTable += `<tr><td>${pos}</td><td>${count}</td></tr>`;
    });
    posTable += `</tbody></table>`;
    const posDiv = document.getElementById('positionSummaryTable');
    if (posDiv) posDiv.innerHTML = posTable;

    // --- 2. Table: Position and Years of Service ---
    // Group by position, then by years_in_teaching range
    const yearRanges = ['0-5', '6-10', '11-15', '16-20', '21+'];
    const posYearCounts = {};
    teachers.forEach(t => {
      if (!posYearCounts[t.position]) posYearCounts[t.position] = [0, 0, 0, 0, 0];
      const y = Number(t.years_in_teaching);
      let idx = 4;
      if (y <= 5) idx = 0;
      else if (y <= 10) idx = 1;
      else if (y <= 15) idx = 2;
      else if (y <= 20) idx = 3;
      posYearCounts[t.position][idx]++;
    });
    let posYearTable = `<table border="1"><thead><tr><th>Position</th>`;
    yearRanges.forEach(r => posYearTable += `<th>${r} yrs</th>`);
    posYearTable += `</tr></thead><tbody>`;
    Object.entries(posYearCounts).forEach(([pos, counts]) => {
      posYearTable += `<tr><td>${pos}</td>`;
      counts.forEach(c => posYearTable += `<td>${c}</td>`);
      posYearTable += `</tr>`;
    });
    posYearTable += `</tbody></table>`;
    const posYearDiv = document.getElementById('positionYearsTable');
    if (posYearDiv) posYearDiv.innerHTML = posYearTable;

    // --- 3. Table: Seminar/Training Attendance by Level ---
    const seminarLevels = ['School-Based', 'Division', 'Region', 'National', 'International'];
    const seminarCounts = { 'School-Based': 0, 'Division': 0, 'Region': 0, 'National': 0, 'International': 0 };
    teachers.forEach(t => {
      if (Array.isArray(t.trainings)) {
        t.trainings.forEach(tr => {
          if (seminarLevels.includes(tr.level)) seminarCounts[tr.level]++;
        });
      }
    });
    let seminarTable = `<table border="1"><thead><tr>`;
    seminarLevels.forEach(lvl => seminarTable += `<th>${lvl}</th>`);
    seminarTable += `</tr></thead><tbody><tr>`;
    seminarLevels.forEach(lvl => seminarTable += `<td>${seminarCounts[lvl]}</td>`);
    seminarTable += `</tr></tbody></table>`;
    const seminarDiv = document.getElementById('seminarSummaryTable');
    if (seminarDiv) seminarDiv.innerHTML = seminarTable;

    // --- 4. Table: Degree Summary (Bachelor's only, Masteral, Doctoral) ---
    let bachelorsOnly = 0, masteral = 0, doctoral = 0;
    teachers.forEach(t => {
      if (Array.isArray(t.education)) {
        const hasDoctoral = t.education.some(e => e.type === 'doctoral');
        const hasMaster = t.education.some(e => e.type === 'master');
        if (hasDoctoral) doctoral++;
        else if (hasMaster) masteral++;
        else bachelorsOnly++;
      } else {
        bachelorsOnly++;
      }
    });
    let degreeTable = `<table border="1"><thead><tr>
      <th>Bachelor's Only</th><th>Masteral</th><th>Doctoral</th>
      </tr></thead><tbody><tr>
      <td>${bachelorsOnly}</td><td>${masteral}</td><td>${doctoral}</td>
      </tr></tbody></table>`;
    const degreeDiv = document.getElementById('degreeSummaryTable');
    if (degreeDiv) degreeDiv.innerHTML = degreeTable;
  }
});
