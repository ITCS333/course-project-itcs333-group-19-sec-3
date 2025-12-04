const listSection = document.getElementById("week-list-section");

function createWeekArticle(week) {
  const article = document.createElement("article");

  const h2 = document.createElement("h2");
  h2.textContent = week.title;
  article.appendChild(h2);

  const startDateP = document.createElement("p");
  startDateP.textContent = "Starts on: " + week.startDate;
  article.appendChild(startDateP);

  const descP = document.createElement("p");
  descP.textContent = week.description;
  article.appendChild(descP);

  const link = document.createElement("a");
  link.href = `details.html?id=${week.id}`;
  link.textContent = "View Details & Discussion";
  article.appendChild(link);

  return article;
}

async function loadWeeks() {
  try {
    const response = await fetch("weeks.json");
    const weeks = await response.json();

    listSection.innerHTML = "";
    weeks.forEach(week => {
      const article = createWeekArticle(week);
      listSection.appendChild(article);
    });
  } catch (error) {
    listSection.innerHTML = "<p>Error loading weeks.</p>";
    console.error(error);
  }
}

loadWeeks();
