const createFormBtn =  document.querySelector(".createForm");
const createFormPopup = document.querySelector('#createFormPopup');
const closepopupBtn = document.querySelector("#closepopupBtn");
const createForm = document.querySelector("#createForm");

createFormBtn.addEventListener('click', () => {
    createFormPopup.style.display = "block";
});

closepopupBtn.addEventListener("click", () => {
    createFormPopup.style.display = "none";
});

createForm.addEventListener("submit", (event) => {
    event.preventDefault();
    const formdata = new FormData(createForm);

    fetch("../backend/api/saveForm.php", {
        method: "POST", 
        body: formdata,
    })
    .then(response => {
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert("Form created Successfully!");
            // console.log(data);
            location.reload();
        } else {
            alert(data.message || "Something went wrong.");
        }
    })
    .catch(error => {
        console.error('Error', error);
        alert('Error'+  error);
    });
});

fetch("../backend/api/fetchforms.php")
.then(response => response.json())
.then(response => {
    if (!response.success) {
        throw new Error(response.message);
    }
    const forms = response.data;
    const tableBody = document.querySelector(".form-table__body");
    if (!tableBody) {
        throw new Error('Table body not found');
    }
    
    tableBody.innerHTML = "";
    if (forms && forms.length > 0) {
        forms.forEach(form => {
            const row = document.createElement('tr');
            row.dataset.formId = form.id;
            row.innerHTML = `
                <td>${form.id}</td>
                <td>${form.title}</td>
                <td>${form.description}</td>
            `;
            tableBody.appendChild(row);
        });
        
        document.querySelectorAll('.form-table__body tr').forEach(row => {
            row.addEventListener('click', () => {
                const formId = row.dataset.formId;
                window.location.href = `../templates/createForm.php?formId=${formId}`;
            });
        });
    } else {
        tableBody.innerHTML = '<tr><td colspan="3">No Forms found...</td></tr>';
    }
})
.catch(error => {
    alert("Error fetching forms: " + error);
});