// Global variables and functions for department cards
window.TMS = window.TMS || {};

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
  const positionFilter = document.getElementById('positionFilter');
  const yearsFilter = document.getElementById('yearsFilter');
  const trainingsFilter = document.getElementById('trainingsFilter');
  const educationFilter = document.getElementById('educationFilter');
  const gradeLevelFilter = document.getElementById('gradeLevelFilter');
  const departmentFilter = document.getElementById('departmentFilter');

  // Fix: Add Teacher button working
  if (addBtn) {
    addBtn.addEventListener('click', () => {
      window.location.href = 'register.html';
    });
  }

  // Tab switching
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      console.log('Tab clicked:', tab.dataset.tab);
      tabs.forEach(t => t.classList.remove('active'));
      tabContents.forEach(c => c.classList.remove('active'));
      tab.classList.add('active');
      document.getElementById(tab.dataset.tab).classList.add('active');
      console.log('Active tab set to:', tab.dataset.tab);
      
      // Handle chart rendering based on active tab
      if (tab.dataset.tab === 'graphTab') {
        // Render charts when graph tab is active
        if (teachersData && teachersData.length > 0) {
          renderCharts(teachersData);
          renderDepartmentCards(teachersData);
        }
      } else {
        // Destroy charts when not on graph tab
        if (positionChart) {
          positionChart.destroy();
          positionChart = null;
        }
        if (yearsChart) {
          yearsChart.destroy();
          yearsChart = null;
        }
        if (ipcrfChart) {
          ipcrfChart.destroy();
          ipcrfChart = null;
        }
      }
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
      window.TMS.teachersData = teachers; // Make globally accessible
      window.TMS.schoolYearSelect = schoolYearSelect;
      window.TMS.teacherSearch = teacherSearch;
      window.TMS.positionFilter = positionFilter;
      window.TMS.yearsFilter = yearsFilter;
      window.TMS.trainingsFilter = trainingsFilter;
      window.TMS.educationFilter = educationFilter;
      window.TMS.gradeLevelFilter = gradeLevelFilter;
      window.TMS.departmentFilter = departmentFilter;


      // Populate position filter dropdown
      if (positionFilter) {
        const uniquePositions = Array.from(new Set(teachersData.map(t => t.position))).sort();
        positionFilter.innerHTML = '<option value="">All Positions</option>' +
          uniquePositions.map(pos => `<option value="${pos}">${pos}</option>`).join('');
      }

      // Populate years in service filter (ranges)
      if (yearsFilter) {
        const yearRanges = ['0-5', '6-10', '11-15', '16-20', '21+'];
        yearsFilter.innerHTML = '<option value="">All Years in Service</option>' +
          yearRanges.map(r => `<option value="${r}">${r} yrs</option>`).join('');
      }

      // Populate trainings filter (by level: School-Based, Division, Region, National, International)
      if (trainingsFilter) {
        const trainingLevels = ['School-Based', 'Division', 'Region', 'National', 'International'];
        trainingsFilter.innerHTML = '<option value="">All Trainings</option>' +
          trainingLevels.map(lvl => `<option value="${lvl}">${lvl}</option>`).join('');
      }

      // Populate education filter (Bachelor, Masteral, Doctoral)
      if (educationFilter) {
        educationFilter.innerHTML = '<option value="">All Educational Attainment</option>' +
          ['Bachelor', 'Masteral', 'Doctoral'].map(e => `<option value="${e}">${e}</option>`).join('');
      }

      // Populate grade level filter
      if (gradeLevelFilter) {
        const uniqueGradeLevels = Array.from(new Set(teachersData.map(t => t.grade_level).filter(gl => gl))).sort();
        gradeLevelFilter.innerHTML = '<option value="">All Grade Levels</option>' +
          uniqueGradeLevels.map(gl => `<option value="${gl}">${gl}</option>`).join('');
      }

      // Populate department filter
      if (departmentFilter) {
        const uniqueDepartments = Array.from(new Set(teachersData.map(t => t.department).filter(d => d))).sort();
        departmentFilter.innerHTML = '<option value="">All Departments</option>' +
          uniqueDepartments.map(dept => `<option value="${dept}">${dept}</option>`).join('');
      }

  // renderTeachers(sortTeachers(teachersData, sortSelect.value));
  renderTeacherTable(teachersData); // Initial render for table
  
  // Only render charts if graph tab is active initially
  const activeTab = document.querySelector('.tab.active');
  if (activeTab && activeTab.dataset.tab === 'graphTab') {
    renderCharts(teachersData); // Initial render for charts
    renderDepartmentCards(teachersData); // Initial render for department cards
  }
  
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
        <td data-label="Full Name">${teacher.full_name}</td>
        <td data-label="Position">${teacher.position}</td>
        <td data-label="Grade Level">${teacher.grade_level || ''}</td>
        <td data-label="Department">${teacher.department || ''}</td>
        <td data-label="Years in Teaching">${teacher.years_in_teaching}</td>
        <td data-label="IPCRF Rating">${teacher.ipcrf_rating}</td>
        <td data-label="School Year">${teacher.school_year}</td>
        <td data-label="Trainings Attended">${Array.isArray(teacher.trainings) ? getTrainingLevels(teacher.trainings) : ''}</td>
        <td data-label="Educational Attainment">${Array.isArray(teacher.education) ? teacher.education.map(e => e.degree).join(', ') : ''}</td>
        <td data-label="Action">
          <button class="view-details-btn" data-id="${teacher.id}">View Details</button>
          <button class="edit-btn" data-id="${teacher.id}">Edit</button>
          <button class="delete-btn" data-id="${teacher.id}">Delete</button>
        </td>
      `;
      teacherTableBody.appendChild(tr);
    });

    // Add event listeners for view details buttons
    document.querySelectorAll('.view-details-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const teacherId = this.getAttribute('data-id');
        const teacher = teachersData.find(t => String(t.id) === String(teacherId));
        if (!teacher) return;
        let trainingsHTML = Array.isArray(teacher.trainings) && teacher.trainings.length > 0
          ? '<ul>' + teacher.trainings.map(t => `<li>${t.title} (${t.date} - ${t.level})</li>`).join('') + '</ul>'
          : '<p>No training data.</p>';
        let educationHTML = '';
        if (Array.isArray(teacher.education) && teacher.education.length > 0) {
          educationHTML = '<ul>' + teacher.education.map(e =>
            `<li>${e.degree} in ${e.major} from ${e.school} (${e.year_attended})` +
            (e.status ? ` - <em>${e.status}</em>` : '') +
            (e.details ? ` - <u>${e.details}</u>` : '') +
            '</li>'
          ).join('') + '</ul>';
        } else {
          educationHTML = '<p>No educational data.</p>';
        }
        document.getElementById('modalBody').innerHTML =
          `<h3>${teacher.full_name}</h3>
           <h4>📚 Trainings Attended</h4>${trainingsHTML}
           <h4>🎓 Educational Attainment</h4>${educationHTML}`;
        document.getElementById('detailsModal').style.display = 'flex';
      });
    });

    // Add event listeners for delete buttons
    document.querySelectorAll('.delete-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const teacherId = this.getAttribute('data-id');
        if (confirm('Are you sure you want to delete this teacher?')) {
          fetch('delete_teacher.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: teacherId })
          })
          .then(res => res.json())
          .then(result => {
            if (result.success) {
              // Remove from teachersData and re-render
              teachersData = teachersData.filter(t => String(t.id) !== String(teacherId));
              updateTeacherFilters();
              alert('Teacher deleted successfully.');
            } else {
              alert('Failed to delete teacher.');
            }
          })
          .catch(() => alert('Error deleting teacher.'));
        }
      });
    });

    // Add event listeners for edit buttons
    document.querySelectorAll('.edit-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const teacherId = this.getAttribute('data-id');
        const teacher = teachersData.find(t => String(t.id) === String(teacherId));
        if (!teacher) return;
        
        openEditModal(teacher);
      });
    });

    // Modal close logic
    const modal = document.getElementById('detailsModal');
    const closeModal = document.getElementById('closeModal');
    const editModal = document.getElementById('editModal');
    
    if (closeModal && modal) {
      closeModal.onclick = () => { modal.style.display = 'none'; };
      window.onclick = (event) => {
        if (event.target === modal) modal.style.display = 'none';
        if (event.target === editModal) editModal.style.display = 'none';
      };
    }
  }

  // Helper to get filtered teachers by year, search, and position
  function getFilteredTeachers() {
    const selectedYear = schoolYearSelect.value;
    const query = teacherSearch.value.trim().toLowerCase();
    const selectedPosition = positionFilter ? positionFilter.value : '';
    const selectedYears = yearsFilter ? yearsFilter.value : '';
    const selectedTraining = trainingsFilter ? trainingsFilter.value : '';
    const selectedEducation = educationFilter ? educationFilter.value : '';
    const selectedGradeLevel = gradeLevelFilter ? gradeLevelFilter.value : '';
    const selectedDepartment = departmentFilter ? departmentFilter.value : '';
    let filtered = teachersData.filter(t => t.school_year === selectedYear);
    if (selectedPosition) {
      filtered = filtered.filter(t => t.position === selectedPosition);
    }
    if (selectedGradeLevel) {
      filtered = filtered.filter(t => t.grade_level === selectedGradeLevel);
    }
    if (selectedDepartment) {
      filtered = filtered.filter(t => t.department === selectedDepartment);
    }
    if (selectedYears) {
      filtered = filtered.filter(t => {
        const y = Number(t.years_in_teaching);
        if (selectedYears === '0-5') return y >= 0 && y <= 5;
        if (selectedYears === '6-10') return y >= 6 && y <= 10;
        if (selectedYears === '11-15') return y >= 11 && y <= 15;
        if (selectedYears === '16-20') return y >= 16 && y <= 20;
        if (selectedYears === '21+') return y >= 21;
        return true;
      });
    }
    if (selectedTraining) {
      filtered = filtered.filter(t => Array.isArray(t.trainings) && t.trainings.some(tr => tr.level === selectedTraining));
    }
    if (selectedEducation) {
      filtered = filtered.filter(t => {
        if (!Array.isArray(t.education)) return false;
        if (selectedEducation === 'Bachelor') {
          // Only bachelor, no masteral or doctoral
          return t.education.some(e => e.type === 'bachelor') &&
            !t.education.some(e => e.type === 'master' || e.type === 'doctoral');
        }
        if (selectedEducation === 'Masteral') {
          return t.education.some(e => e.type === 'master') &&
            !t.education.some(e => e.type === 'doctoral');
        }
        if (selectedEducation === 'Doctoral') {
          return t.education.some(e => e.type === 'doctoral');
        }
        return true;
      });
    }
    if (query) {
      filtered = filtered.filter(t =>
        t.full_name.toLowerCase().includes(query) ||
        t.position.toLowerCase().includes(query) ||
        String(t.years_in_teaching).includes(query) ||
        String(t.ipcrf_rating).includes(query)
      );
    }
    return filtered;
  }

  // Make getFilteredTeachers globally accessible
  window.TMS.getFilteredTeachers = getFilteredTeachers;

  // Filter and render on school year change

  // Unified filter update for year, search, and position
  function updateTeacherFilters() {
    const filtered = getFilteredTeachers();
  renderTeacherTable(filtered);
  // renderTeachers(sortTeachers(filtered, sortSelect.value));
  renderCharts(filtered);
  renderDepartmentCards(filtered);
  renderSummaryTables(filtered);
  }

  schoolYearSelect.addEventListener('change', updateTeacherFilters);
  teacherSearch.addEventListener('input', updateTeacherFilters);
  if (positionFilter) positionFilter.addEventListener('change', updateTeacherFilters);
  if (yearsFilter) yearsFilter.addEventListener('change', updateTeacherFilters);
  if (trainingsFilter) trainingsFilter.addEventListener('change', updateTeacherFilters);
  if (educationFilter) educationFilter.addEventListener('change', updateTeacherFilters);
  if (gradeLevelFilter) gradeLevelFilter.addEventListener('change', updateTeacherFilters);
  if (departmentFilter) departmentFilter.addEventListener('change', updateTeacherFilters);

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
    renderTeacherTable(sortTeachers(teachersData, sortSelect.value));
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
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        aspectRatio: 1,
        plugins: {
          title: {
            display: true,
            text: 'Teachers by Position',
            font: {
              size: 16,
              weight: 'bold'
            },
            padding: {
              top: 10,
              bottom: 20
            }
          },
          legend: {
            display: true
          }
        },
        scales: {
          y: {
            beginAtZero: true
          }
        }
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
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        aspectRatio: 1,
        plugins: {
          title: {
            display: true,
            text: 'Years in Service Distribution',
            font: {
              size: 16,
              weight: 'bold'
            },
            padding: {
              top: 10,
              bottom: 20
            }
          },
          legend: {
            display: true,
            position: 'bottom'
          }
        }
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
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        aspectRatio: 1,
        plugins: {
          title: {
            display: true,
            text: 'IPCRF Rating Distribution',
            font: {
              size: 16,
              weight: 'bold'
            },
            padding: {
              top: 10,
              bottom: 20
            }
          },
          legend: {
            display: true,
            position: 'bottom'
          }
        }
      }
    });
  }

  function renderDepartmentCards(teachers) {
    // Get all teachers data for total counts
    const allTeachersData = teachersData; // Global variable with all teachers
    const currentFilteredTeachers = teachers; // Teachers currently displayed/filtered
    
    // Load custom totals from localStorage
    const customTotals = JSON.parse(localStorage.getItem('departmentCustomTotals') || '{}');
    
    // Calculate department counts
    const allDepartmentCounts = {};
    const currentDepartmentCounts = {};
    
    // Count all teachers by department
    allTeachersData.forEach(t => {
      if (t.department) {
        allDepartmentCounts[t.department] = (allDepartmentCounts[t.department] || 0) + 1;
      }
    });
    
    // Count currently filtered/displayed teachers by department
    currentFilteredTeachers.forEach(t => {
      if (t.department) {
        currentDepartmentCounts[t.department] = (currentDepartmentCounts[t.department] || 0) + 1;
      }
    });
    
    // Get all unique departments (include departments with custom totals even if no teachers)
    const allDepartments = [...new Set([
      ...Object.keys(allDepartmentCounts),
      ...Object.keys(customTotals)
    ])].sort();
    
    const container = document.getElementById('departmentCardsContainer');
    if (!container) return;
    
    container.innerHTML = '';
    
    allDepartments.forEach(department => {
      // Use custom total if set, otherwise use actual count
      const totalCount = customTotals[department] !== undefined ? customTotals[department] : (allDepartmentCounts[department] || 0);
      const currentCount = currentDepartmentCounts[department] || 0;
      const completionRate = totalCount > 0 ? Math.round((currentCount / totalCount) * 100) : 0;
      
      // Generate CSS class name from department name
      const cssClass = department.toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^a-z0-9-]/g, '');
      
      const card = document.createElement('div');
      card.className = `department-card ${cssClass}`;
      
      card.innerHTML = `
        <h3>${department}</h3>
        <div class="department-stats">
          <div class="stat-row editable" data-department="${department}">
            <span class="stat-label">Total Teachers:</span>
            <span class="stat-value">${totalCount}</span>
            <button class="edit-total-btn" onclick="editTotalTeachers('${department}', ${totalCount})">✏️</button>
          </div>
          <div class="stat-row">
            <span class="stat-label">Currently Shown:</span>
            <span class="stat-value">${currentCount}</span>
          </div>
          <div class="completion-rate">
            <div class="stat-label">Representation</div>
            <div class="completion-percentage">${completionRate}%</div>
          </div>
        </div>
      `;
      
      container.appendChild(card);
    });
  }

  // Make renderDepartmentCards globally accessible
  window.TMS.renderDepartmentCards = renderDepartmentCards;

  function renderSummaryTables(teachers) {
    // --- 1. Number of teachers per position ---
    const positionCounts = {};
    teachers.forEach(t => {
      positionCounts[t.position] = (positionCounts[t.position] || 0) + 1;
    });
  let posTable = `<table class="summary-table"><thead><tr><th>Position</th><th>Number of Teachers</th></tr></thead><tbody>`;
    Object.entries(positionCounts).forEach(([pos, count]) => {
      posTable += `<tr><td data-label="Position">${pos}</td><td data-label="Number of Teachers">${count}</td></tr>`;
    });
    posTable += `</tbody></table>`;
    const posDiv = document.getElementById('positionSummaryTable');
    if (posDiv) posDiv.innerHTML = posTable;

    // --- 2. Number of teachers per grade level ---
    const gradeLevelCounts = {};
    teachers.forEach(t => {
      const gradeLevel = t.grade_level || 'Not Specified';
      gradeLevelCounts[gradeLevel] = (gradeLevelCounts[gradeLevel] || 0) + 1;
    });
    let gradeLevelTable = `<table class="summary-table"><thead><tr><th>Grade Level</th><th>Number of Teachers</th></tr></thead><tbody>`;
    Object.entries(gradeLevelCounts).forEach(([grade, count]) => {
      gradeLevelTable += `<tr><td data-label="Grade Level">${grade}</td><td data-label="Number of Teachers">${count}</td></tr>`;
    });
    gradeLevelTable += `</tbody></table>`;
    const gradeLevelDiv = document.getElementById('gradeLevelSummaryTable');
    if (gradeLevelDiv) gradeLevelDiv.innerHTML = gradeLevelTable;

    // --- 3. Number of teachers per department ---
    const departmentCounts = {};
    teachers.forEach(t => {
      const department = t.department || 'Not Specified';
      departmentCounts[department] = (departmentCounts[department] || 0) + 1;
    });
    let departmentTable = `<table class="summary-table"><thead><tr><th>Department</th><th>Number of Teachers</th></tr></thead><tbody>`;
    Object.entries(departmentCounts).forEach(([dept, count]) => {
      departmentTable += `<tr><td data-label="Department">${dept}</td><td data-label="Number of Teachers">${count}</td></tr>`;
    });
    departmentTable += `</tbody></table>`;
    const departmentDiv = document.getElementById('departmentSummaryTable');
    if (departmentDiv) departmentDiv.innerHTML = departmentTable;

    // --- 4. Table: Position and Years of Service ---
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
  let posYearTable = `<table class="summary-table"><thead><tr><th>Position</th>`;
    yearRanges.forEach(r => posYearTable += `<th>${r} yrs</th>`);
    posYearTable += `</tr></thead><tbody>`;
    Object.entries(posYearCounts).forEach(([pos, counts]) => {
      posYearTable += `<tr><td data-label="Position">${pos}</td>`;
      counts.forEach((c, idx) => posYearTable += `<td data-label="${yearRanges[idx]} yrs">${c}</td>`);
      posYearTable += `</tr>`;
    });
    posYearTable += `</tbody></table>`;
    const posYearDiv = document.getElementById('positionYearsTable');
    if (posYearDiv) posYearDiv.innerHTML = posYearTable;

    // --- 5. Table: Seminar/Training Attendance by Level ---
    const seminarLevels = ['School-Based', 'Division', 'Region', 'National', 'International'];
    const seminarCounts = { 'School-Based': 0, 'Division': 0, 'Region': 0, 'National': 0, 'International': 0 };
    teachers.forEach(t => {
      if (Array.isArray(t.trainings)) {
        t.trainings.forEach(tr => {
          if (seminarLevels.includes(tr.level)) seminarCounts[tr.level]++;
        });
      }
    });
  let seminarTable = `<table class="summary-table"><thead><tr>`;
    seminarLevels.forEach(lvl => seminarTable += `<th>${lvl}</th>`);
    seminarTable += `</tr></thead><tbody><tr>`;
    seminarLevels.forEach(lvl => seminarTable += `<td data-label="${lvl}">${seminarCounts[lvl]}</td>`);
    seminarTable += `</tr></tbody></table>`;
    const seminarDiv = document.getElementById('seminarSummaryTable');
    if (seminarDiv) seminarDiv.innerHTML = seminarTable;

    // --- 6. Table: Degree Summary (Bachelor's only, Masteral, Doctoral) ---
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
    let degreeTable = `<table class="summary-table"><thead><tr>
      <th>Bachelor's Only</th><th>Masteral</th><th>Doctoral</th>
      </tr></thead><tbody><tr>
      <td data-label="Bachelor's Only">${bachelorsOnly}</td><td data-label="Masteral">${masteral}</td><td data-label="Doctoral">${doctoral}</td>
      </tr></tbody></table>`;
    const degreeDiv = document.getElementById('degreeSummaryTable');
    if (degreeDiv) degreeDiv.innerHTML = degreeTable;
  }

  // Add this function to categorize trainings by level
  function getTrainingLevels(trainings) {
    // Initialize counters for each level
    const levels = {
      'School-Based': 0,
      'Division': 0,
      'Regional': 0, 
      'National': 0,
      'International': 0
    };
    
    // Count trainings by level
    trainings.forEach(training => {
      // Assuming each training object has a "level" property
      // If your data structure is different, adjust accordingly
      if (training.level && levels.hasOwnProperty(training.level)) {
        levels[training.level]++;
      }
    });
    
    // Format the output as a string
    return Object.entries(levels)
      .filter(([_, count]) => count > 0)
      .map(([level, count]) => `${level}: ${count}`)
      .join('<br>');
  }

  // Then in your code that populates the table rows, replace the trainings cell content
  // Instead of something like:
  // cell.textContent = teacher.trainings.map(t => t.name).join(", ");
  // Use:
  // cell.innerHTML = getTrainingLevels(teacher.trainings);

  // Edit modal functionality
  function openEditModal(teacher) {
    // Populate basic form fields
    document.getElementById('editTeacherId').value = teacher.id;
    document.getElementById('editFullName').value = teacher.full_name;
    document.getElementById('editPosition').value = teacher.position;
    document.getElementById('editGradeLevel').value = teacher.grade_level || '';
    document.getElementById('editDepartment').value = teacher.department || '';
    document.getElementById('editYearsInTeaching').value = teacher.years_in_teaching;
    document.getElementById('editIpcrfRating').value = teacher.ipcrf_rating;
    document.getElementById('editSchoolYear').value = teacher.school_year;

    // Clear existing training and education entries
    document.getElementById('editTrainingList').innerHTML = '';
    document.getElementById('editBachelorList').innerHTML = '';
    document.getElementById('editMasterList').innerHTML = '';
    document.getElementById('editDoctoralList').innerHTML = '';

    // Populate trainings
    if (Array.isArray(teacher.trainings)) {
      teacher.trainings.forEach(training => {
        addEditTraining(training.title, training.date, training.level);
      });
    }

    // Populate education
    if (Array.isArray(teacher.education)) {
      teacher.education.forEach(edu => {
        addEditEducation(edu.degree.toLowerCase().includes('bachelor') ? 'bachelor' :
                         edu.degree.toLowerCase().includes('master') ? 'master' : 'doctoral',
                         edu.degree, edu.school, edu.major, edu.year_attended, edu.status, edu.details);
      });
    }

    // Show the modal
    document.getElementById('editModal').style.display = 'flex';
  }

  function addEditTraining(title = '', date = '', level = '') {
    const trainingList = document.getElementById('editTrainingList');
    const div = document.createElement('div');
    div.classList.add('training-entry');

    div.innerHTML = `
      <input type="text" placeholder="Training Title" class="training-title" value="${title}" required>
      <input type="date" class="training-date" value="${date}" required>
      <select class="training-level" required>
        <option value="">Select Level</option>
        <option value="School-Based" ${level === 'School-Based' ? 'selected' : ''}>School-Based</option>
        <option value="District" ${level === 'District' ? 'selected' : ''}>District</option>
        <option value="Division" ${level === 'Division' ? 'selected' : ''}>Division</option>
        <option value="Regional" ${level === 'Regional' ? 'selected' : ''}>Regional</option>
        <option value="National" ${level === 'National' ? 'selected' : ''}>National</option>
        <option value="International" ${level === 'International' ? 'selected' : ''}>International</option>
      </select>
      <button type="button" class="remove-btn">🗑️</button>
    `;

    div.querySelector('.remove-btn').addEventListener('click', () => {
      div.remove();
    });

    trainingList.appendChild(div);
  }

  function addEditEducation(type, degree = '', school = '', major = '', year = '', status = '', details = '') {
    const container = {
      bachelor: document.getElementById('editBachelorList'),
      master: document.getElementById('editMasterList'),
      doctoral: document.getElementById('editDoctoralList')
    }[type];

    const div = document.createElement('div');
    div.classList.add('education-entry');

    const yearId = `edit-${type}-year-${Date.now()}`;

    div.innerHTML = `
      <input type="text" placeholder="Degree" class="degree" value="${degree}" required>
      <input type="text" placeholder="School" class="school" value="${school}" required>
      <input type="text" placeholder="Major" class="major" value="${major}" required>
      <input type="text" placeholder="Year Attended" id="${yearId}" class="year" value="${year}" required>
      <select class="status" required>
        <option value="">Status</option>
        <option value="Graduated" ${status === 'Graduated' ? 'selected' : ''}>Graduated</option>
        <option value="Undergraduate" ${status === 'Undergraduate' ? 'selected' : ''}>Undergraduate</option>
        <option value="With Units" ${status === 'With Units' ? 'selected' : ''}>With Units</option>
      </select>
      <input type="text" placeholder="Details (e.g., Thesis Title or Earned Units)" class="details" value="${details}">
      <button type="button" class="remove-btn">🗑️</button>
    `;

    div.querySelector('.remove-btn').addEventListener('click', () => {
      div.remove();
    });

    container.appendChild(div);

    flatpickr(`#${yearId}`, {
      dateFormat: "Y",
      allowInput: true
    });
  }

  // Event listeners for edit modal
  document.getElementById('editAddTrainingBtn')?.addEventListener('click', () => {
    addEditTraining();
  });

  document.querySelectorAll('.editAddEducationBtn')?.forEach(btn => {
    btn.addEventListener('click', () => {
      const type = btn.dataset.type;
      if (type) addEditEducation(type);
    });
  });

  // Close edit modal
  document.getElementById('closeEditModal')?.addEventListener('click', () => {
    document.getElementById('editModal').style.display = 'none';
  });

  // Edit form submission
  document.getElementById('editTeacherForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData();
    
    // Basic teacher data
    formData.append('teacherId', document.getElementById('editTeacherId').value);
    formData.append('fullName', document.getElementById('editFullName').value);
    formData.append('position', document.getElementById('editPosition').value);
    formData.append('gradeLevel', document.getElementById('editGradeLevel').value);
    formData.append('department', document.getElementById('editDepartment').value);
    formData.append('yearsInTeaching', document.getElementById('editYearsInTeaching').value);
    formData.append('ipcrfRating', document.getElementById('editIpcrfRating').value);
    formData.append('schoolYear', document.getElementById('editSchoolYear').value);

    // Training data
    const trainingEntries = document.getElementById('editTrainingList').querySelectorAll('.training-entry');
    const trainingData = Array.from(trainingEntries).map(entry => ({
      title: entry.querySelector('.training-title')?.value.trim(),
      date: entry.querySelector('.training-date')?.value,
      level: entry.querySelector('.training-level')?.value
    })).filter(t => t.title && t.date && t.level);

    // Education data
    const getEducationFrom = (list) =>
      Array.from(list.querySelectorAll('.education-entry')).map(entry => ({
        degree: entry.querySelector('.degree')?.value.trim(),
        school: entry.querySelector('.school')?.value.trim(),
        major: entry.querySelector('.major')?.value.trim(),
        year_attended: entry.querySelector('.year')?.value.trim(),
        status: entry.querySelector('.status')?.value.trim(),
        details: entry.querySelector('.details')?.value.trim()
      })).filter(e => e.degree && e.school && e.major);

    const educationData = [
      ...getEducationFrom(document.getElementById('editBachelorList')),
      ...getEducationFrom(document.getElementById('editMasterList')),
      ...getEducationFrom(document.getElementById('editDoctoralList'))
    ];

    formData.append('trainingData', JSON.stringify(trainingData));
    formData.append('educationData', JSON.stringify(educationData));

    try {
      const response = await fetch('update_teacher.php', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if (result.success) {
        alert('Teacher updated successfully!');
        document.getElementById('editModal').style.display = 'none';
        
        // Refresh the teacher data
        window.location.reload();
      } else {
        alert('Error: ' + (result.message || 'Failed to update teacher'));
      }
    } catch (error) {
      alert('Error updating teacher: ' + error.message);
    }
  });
});

// Global functions for department total editing
function editTotalTeachers(department, currentTotal) {
  const statRow = document.querySelector(`.stat-row[data-department="${department}"]`);
  if (!statRow || statRow.classList.contains('editing')) return;
  
  statRow.classList.add('editing');
  
  const statValue = statRow.querySelector('.stat-value');
  const editBtn = statRow.querySelector('.edit-total-btn');
  
  // Hide the current value and edit button
  statValue.style.display = 'none';
  editBtn.style.display = 'none';
  
  // Create input and controls
  const inputContainer = document.createElement('div');
  inputContainer.className = 'edit-total-controls';
  inputContainer.innerHTML = `
    <input type="number" class="edit-total-input" value="${currentTotal}" min="0" max="999">
    <button class="save-total-btn" onclick="saveTotalTeachers('${department}')">✓</button>
    <button class="cancel-total-btn" onclick="cancelEditTotal('${department}')">✕</button>
  `;
  
  statRow.appendChild(inputContainer);
  
  // Focus the input
  const input = inputContainer.querySelector('.edit-total-input');
  input.focus();
  input.select();
  
  // Handle Enter key
  input.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
      saveTotalTeachers(department);
    } else if (e.key === 'Escape') {
      cancelEditTotal(department);
    }
  });
}

function saveTotalTeachers(department) {
  const statRow = document.querySelector(`.stat-row[data-department="${department}"]`);
  if (!statRow) return;
  
  const input = statRow.querySelector('.edit-total-input');
  const newTotal = parseInt(input.value) || 0;
  
  // Save to localStorage
  const customTotals = JSON.parse(localStorage.getItem('departmentCustomTotals') || '{}');
  customTotals[department] = newTotal;
  localStorage.setItem('departmentCustomTotals', JSON.stringify(customTotals));
  
  // Refresh the cards to show new calculation
  if (window.TMS && window.TMS.getFilteredTeachers) {
    const filtered = window.TMS.getFilteredTeachers();
    // Find and call renderDepartmentCards if available
    if (window.TMS.renderDepartmentCards) {
      window.TMS.renderDepartmentCards(filtered);
    } else {
      // Fallback: refresh the page if functions not available
      location.reload();
    }
  } else {
    // Fallback: refresh the page
    location.reload();
  }
}

function cancelEditTotal(department) {
  const statRow = document.querySelector(`.stat-row[data-department="${department}"]`);
  if (!statRow) return;
  
  statRow.classList.remove('editing');
  
  // Remove input controls
  const inputContainer = statRow.querySelector('.edit-total-controls');
  if (inputContainer) {
    inputContainer.remove();
  }
  
  // Show original elements
  const statValue = statRow.querySelector('.stat-value');
  const editBtn = statRow.querySelector('.edit-total-btn');
  statValue.style.display = 'inline';
  editBtn.style.display = 'inline';
}

// Function to reset all custom totals
function resetAllCustomTotals() {
  if (confirm('Are you sure you want to reset all custom totals to their original values?')) {
    localStorage.removeItem('departmentCustomTotals');
    
    if (window.TMS && window.TMS.getFilteredTeachers && window.TMS.renderDepartmentCards) {
      const filtered = window.TMS.getFilteredTeachers();
      window.TMS.renderDepartmentCards(filtered);
    } else {
      // Fallback: refresh the page
      location.reload();
    }
  }
}
