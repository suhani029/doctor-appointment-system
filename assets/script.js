document.addEventListener("DOMContentLoaded", function () {
    const searchBox = document.getElementById("tableSearch");
    const table = document.getElementById("searchTable");

    if (searchBox && table) {
        searchBox.addEventListener("input", function () {
            const searchText = this.value.toLowerCase();
            const rows = table.querySelectorAll("tbody tr");

            rows.forEach(function (row) {
                const rowText = row.innerText.toLowerCase();
                row.style.display = rowText.includes(searchText) ? "" : "none";
            });
        });
    }
});
