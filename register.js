document.addEventListener("DOMContentLoaded", () => {
  const addTrainingBtn = document.getElementById("addTrainingBtn");
  const addEducationBtns = document.querySelectorAll(".addEducationBtn");
  const teacherForm = document.getElementById("teacherForm");

  const trainingList = document.getElementById("trainingList");
  const bachelorList = document.getElementById("bachelorList");
  const masterList = document.getElementById("masterList");
  const doctoralList = document.getElementById("doctoralList");

  // TRAINING
  addTrainingBtn?.addEventListener("click", () => {
    const div = document.createElement("div");
    div.classList.add("training-entry");

    div.innerHTML = `
      <input type="text" placeholder="Training Title" class="training-title" required>
      <input type="date" class="training-date" required>
      <select class="training-level" required>
        <option value="">Select Level</option>
        <option value="School-Based">School-Based</option>
        <option value="District">District</option>
        <option value="Division">Division</option>
        <option value="Regional">Regional</option>
        <option value="National">National</option>
        <option value="International">International</option>
      </select>
      <button type="button" class="remove-btn">🗑️</button>
    `;

    div.querySelector(".remove-btn").addEventListener("click", () => {
      div.remove();
    });

    trainingList?.appendChild(div);
  });

  // EDUCATION
  const addEducation = (type) => {
    const container = { bachelor: bachelorList, master: masterList, doctoral: doctoralList }[type];
    const div = document.createElement("div");
    div.classList.add("education-entry");

    const yearId = `${type}-year-${Date.now()}`;

    div.innerHTML = `
      <input type="text" placeholder="Degree" class="degree" required>
      <input type="text" placeholder="School" class="school" required>
      <input type="text" placeholder="Major" class="major" required>
      <input type="text" placeholder="Year Attended" id="${yearId}" class="year" required>
      <select class="status" required>
        <option value="">Status</option>
        <option value="Graduated">Graduated</option>
        <option value="Undergraduate">Undergraduate</option>
        <option value="With Units">With Units</option>
      </select>
      <input type="text" placeholder="Details (e.g., Thesis Title or Earned Units)" class="details">
      <button type="button" class="remove-btn">🗑️</button>
    `;

    div.querySelector(".remove-btn").addEventListener("click", () => {
      div.remove();
    });

    container?.appendChild(div);

    flatpickr(`#${yearId}`, {
      dateFormat: "Y",
      allowInput: true
    });
  };

  addEducationBtns?.forEach((btn) => {
    btn.addEventListener("click", () => {
      const type = btn.dataset.type;
      if (type) addEducation(type);
    });
  });

  // SUBMIT
  teacherForm?.addEventListener("submit", async (e) => {
    e.preventDefault();

    const fullName = teacherForm.fullName?.value.trim();
    const position = teacherForm.position?.value.trim();
    const years = teacherForm.yearsInTeaching?.value.trim();
    const ipcrf = teacherForm.ipcrfRating?.value.trim(); // Corrected name: ipcrfRating
    const schoolYear = teacherForm.schoolYear?.value.trim();

    const trainingEntries = trainingList.querySelectorAll(".training-entry");
    const trainingData = Array.from(trainingEntries).map(entry => ({
      title: entry.querySelector(".training-title")?.value.trim(),
      date: entry.querySelector(".training-date")?.value,
      level: entry.querySelector(".training-level")?.value
    })); // Removed .filter() here

    const getEducationFrom = (list) =>
      Array.from(list.querySelectorAll(".education-entry")).map(entry => ({
        degree: entry.querySelector(".degree")?.value.trim(),
        school: entry.querySelector(".school")?.value.trim(),
        major: entry.querySelector(".major")?.value.trim(),
        year: entry.querySelector(".year")?.value.trim(),
        status: entry.querySelector(".status")?.value,
        details: entry.querySelector(".details")?.value.trim()
      })); // Removed .filter() here

    const educationData = {
      bachelor: getEducationFrom(bachelorList),
      master: getEducationFrom(masterList),
      doctoral: getEducationFrom(doctoralList),
    };

    // Client-side validation for at least one fully filled training and education record
    const hasValidTraining = trainingData.some(t => t.title && t.date && t.level);
    const hasValidEducation =
      educationData.bachelor.some(e => e.degree && e.school && e.major && e.year && e.status) ||
      educationData.master.some(e => e.degree && e.school && e.major && e.year && e.status) ||
      educationData.doctoral.some(e => e.degree && e.school && e.major && e.year && e.status);

    if (!fullName || !position || !years || !ipcrf || !schoolYear || !hasValidTraining || !hasValidEducation) {
      alert("Please complete all required fields including at least one *fully filled* training and one *fully filled* educational record.");
      return;
    }

    const payload = new FormData();
    payload.append("fullName", fullName);
    payload.append("position", position);
    payload.append("yearsInTeaching", years);
    payload.append("ipcrfRating", ipcrf);
    payload.append("schoolYear", schoolYear);
    payload.append("trainingData", JSON.stringify(trainingData.filter(t => t.title && t.date && t.level))); // Filter here before sending
    payload.append("educationData", JSON.stringify({
      bachelor: educationData.bachelor.filter(e => e.degree && e.school && e.major && e.year && e.status),
      master: educationData.master.filter(e => e.degree && e.school && e.major && e.year && e.status),
      doctoral: educationData.doctoral.filter(e => e.degree && e.school && e.major && e.year && e.status),
    })); // Filter here before sending

    for (let [key, value] of payload.entries()) {
      console.log(key, value);
    }

    try {
      console.log("🔄 Sending data to server...");
      const res = await fetch("register_teacher.php", {
        method: "POST",
        body: payload,
      });

      const json = await res.json();
      console.log("📩 Server responded:", json);

      if (json.success) {
        alert("✅ Registration successful!");
        teacherForm.reset();
        trainingList.innerHTML = "";
        bachelorList.innerHTML = "";
        masterList.innerHTML = "";
        doctoralList.innerHTML = "";
      } else {
        alert("⚠️ Error: " + json.message);
      }
    } catch (err) {
      console.error("❌ Fetch error:", err);
      alert("An unexpected error occurred.");
    }
  });
});