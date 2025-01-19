"use strict";

// Gestion des likes, commentaires et dislikes

function likePost(postId, cardElement) {
  fetch("index.php?page=like-post&id=" + postId).then((response) => {
    if (response.ok) {
      if (isSwipePage()) {
        cardElement.remove();
        verifierCartesRestante();
      } else if (isOneCategoriePage()) {
        window.location.reload();
      }
    }
  });
}

function commentairePost(postId) {
  const commentInput = document.getElementById(`comment-input-${postId}`);
  commentInput.classList.toggle("hidden");
}

function submitCommentaire(postId) {
  const inputField = document.querySelector(`#comment-input-${postId} input`);
  const comment = inputField.value;
  if (comment) {
    const postIdInt = parseInt(postId);

    fetch("index.php?page=store_commentaire", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `commentaire=${encodeURIComponent(
        comment
      )}&id_post=${postIdInt}&id_user=${encodeURIComponent(userId)}`,
    })
      .then((response) => {
        if (response.ok) {
          inputField.value = "";
          commentairePost(postId);
          window.location.reload();
        } else {
          alert("Erreur lors de l'envoi du commentaire.");
        }
      })
      .catch((error) => {
        console.error("Erreur:", error);
      });
  } else {
    alert("Veuillez entrer un commentaire.");
  }
}

function dislikePost(postId, cardElement) {
  fetch("index.php?page=dislike-post&id=" + postId).then((response) => {
    if (response.ok) {
      if (isSwipePage()) {
        cardElement.remove();
        verifierCartesRestante();
      } else if (isOneCategoriePage()) {
        window.location.reload();
      }
    }
  });
}

function isSwipePage() {
  return window.location.href.includes("page=swipe");
}

function isOneCategoriePage() {
  return window.location.href.includes("page=galerie-categorie");
}

function getCurrentCardIndex() {
  const remainingCards = document.querySelectorAll(".card");
  return remainingCards.length - 1;
}

function verifierCartesRestante() {
  const remainingCards = document.querySelectorAll(".card-image");
  if (remainingCards.length === 0) {
    document.querySelector(".bg-blue-600").innerHTML =
      '<div class="text-center text-lg"><h3>Oops!</h3><p>Nous ne trouvons plus de photos à vous proposer, veuillez réessayer plus tard.</p></div>';
    setTimeout(() => {
      window.location.href = "index.php?page=galerie";
    }, 1000);
  } else {
    remainingCards.forEach((card, newIndex) => {
      card.style.zIndex = 10 - newIndex;
    });
  }
}

document.querySelectorAll(".dislike-button").forEach((button) => {
  button.addEventListener("click", () => {
    const postId = button.getAttribute("data-post-id");
    dislikePost(postId, button.closest(".card"));
  });
});

document.querySelectorAll(".comment-button").forEach((button) => {
  button.addEventListener("click", () => {
    const postId = button.getAttribute("data-post-id");
    commentairePost(postId);
  });
});

document.querySelectorAll(".submit-comment-button").forEach((button) => {
  button.addEventListener("click", () => {
    const postId = button.getAttribute("data-post-id");
    submitCommentaire(postId);
  });
});

document.querySelectorAll(".like-button").forEach((button) => {
  button.addEventListener("click", () => {
    const postId = button.getAttribute("data-post-id");
    likePost(postId, button.closest(".card"));
  });
});

// Gestion des catégories favorites

let categories = [];

function choixCategories(nomCategorie, bouton) {
  const index = categories.indexOf(nomCategorie);
  const boutonContinuer = document.getElementById("continue-button");

  if (index > -1) {
    categories.splice(index, 1);
    bouton.classList.remove("selected");
  } else {
    if (categories.length < 3) {
      categories.push(nomCategorie);
      bouton.classList.add("selected");
    } else {
      alert("Vous ne pouvez sélectionner que 3 catégories.");
    }
  }
  boutonContinuer.textContent = `CONTINUER ${categories.length}/3`;
  boutonContinuer.disabled = categories.length < 3;
}

function submitCategories() {
  const formData = new FormData();
  formData.append("categories", JSON.stringify(categories));

  fetch("index.php?page=store-favorite-categories", {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      if (response.ok) {
        const nomCategories = categories.join(",");
        window.location.href = `index.php?page=swipe&category_names=${encodeURIComponent(
          nomCategories
        )}`;
      } else {
        alert("Erreur lors de l'enregistrement des catégories favorites.");
      }
    })
    .catch((error) => {
      console.error("Erreur:", error);
    });
}

document.querySelectorAll("[id='boutonSelection']").forEach((button) => {
  button.addEventListener("click", () => {
    const categorieNom = button.textContent.trim();
    choixCategories(categorieNom, button);
    button.classList.add("bg-pinkTailwind", "text-white");
  });
});

const boutonContinuer = document.getElementById("continue-button");
if (boutonContinuer) {
  boutonContinuer.addEventListener("click", () => {
    submitCategories();
  });
}

// Animation swipe des cartes

let currentCardIndex = 0;
let isDragging = false;
let startX = 0;
let currentTranslate = 0;

const cards = document.querySelectorAll(".card-image");

function touchStart(e, index) {
  isDragging = true;
  startX = e.type === "touchstart" ? e.touches[0].clientX : e.clientX;
  currentCardIndex = index;
}

function touchMove(e) {
  if (!isDragging) return;
  const currentPosition =
    e.type === "touchmove" ? e.touches[0].clientX : e.clientX;
  const deltaX = currentPosition - startX;
  currentTranslate = deltaX;
  cards[currentCardIndex].style.transform = `translateX(${deltaX}px) rotate(${
    deltaX / 20
  }deg)`;
}

function touchEnd() {
  if (!isDragging) return;
  isDragging = false;
  const threshold = 150;
  if (currentTranslate > threshold) {
    swipeRight(currentCardIndex);
  } else if (currentTranslate < -threshold) {
    swipeLeft(currentCardIndex);
  } else {
    resetCardPosition(currentCardIndex);
  }
  currentTranslate = 0;
}

function swipeLeft(index) {
  const card = cards[index];
  const postId = card.dataset.postId;

  dislikePost(postId, card);
  card.style.transform = `translateX(-100%) rotate(-30deg)`;
  card.style.opacity = 0;
  setTimeout(() => {
    card.remove();
    verifierCartesRestante();
  }, 300);
}

function swipeRight(index) {
  const card = cards[index];
  const postId = card.dataset.postId;

  likePost(postId, card);
  card.style.transform = `translateX(100%) rotate(30deg)`;
  card.style.opacity = 0;
  setTimeout(() => {
    card.remove();
    verifierCartesRestante();
  }, 300);
}

function resetCardPosition(index) {
  cards[index].style.transform = "translateX(0) rotate(0)";
  cards[index].style.transition = "transform 0.3s ease";
}

cards.forEach((card, index) => {
  card.addEventListener("mousedown", (e) => touchStart(e, index));
  card.addEventListener("touchstart", (e) => touchStart(e, index));
  card.addEventListener("mousemove", (e) => touchMove(e));
  card.addEventListener("touchmove", (e) => touchMove(e));
  card.addEventListener("mouseup", touchEnd);
  card.addEventListener("touchend", touchEnd);
  card.addEventListener("mouseleave", touchEnd);
});

function resetCard(card) {
  card.style.transition = "transform 0.3s ease, opacity 0.3s ease";
  card.style.transform = "translateX(0) rotate(0)";
  card.style.opacity = 1;
}
function getPositionX(e) {
  return e.type.includes("mouse") ? e.clientX : e.touches[0].clientX;
}

// Redirection des pages

const categorieGalerie = document.querySelectorAll(".categorie-galerie");
categorieGalerie.forEach((categorie) => {
  categorie.addEventListener("click", () => {
    window.location.href =
      "index.php?page=galerie-categorie&nom_categorie=" +
      categorie.getAttribute("data-nom-categorie");
  });
});

document.querySelectorAll(".commentaire").forEach((button) => {
  button.addEventListener("click", () => {
    const postId = button.getAttribute("data-post-id");
    document
      .getElementById("comments-list-" + postId)
      .classList.toggle("hidden");
  });
});

const toggleLikedPosts = document.getElementById("toggle-liked-posts");
const toggleDislikedPosts = document.getElementById("toggle-disliked-posts");

if (toggleLikedPosts) {
  toggleLikedPosts.addEventListener("click", () => {
    document.getElementById("more-liked-posts").classList.toggle("hidden");
  });
}

if (toggleDislikedPosts) {
  toggleDislikedPosts.addEventListener("click", () => {
    document.getElementById("more-disliked-posts").classList.toggle("hidden");
  });
}

// Gestion des erreurs connexion / inscription

function validateForm(event) {
  event.preventDefault();

  const nom = document.getElementById("nom")
    ? document.getElementById("nom").value
    : "";
  const pseudo = document.getElementById("pseudo")
    ? document.getElementById("pseudo").value
    : "";
  const age = document.getElementById("age")
    ? parseInt(document.getElementById("age").value)
    : null;
  const email = document.getElementById("email")
    ? document.getElementById("email").value
    : "";
  const errorDiv = document.querySelector(".erreur p");
  errorDiv.textContent = "";

  try {
    const nameRegex = /^[a-zA-ZÀ-ÿ\s'-]{3,50}$/;
    if (!nameRegex.test(nom) || !nameRegex.test(pseudo)) {
      throw new Error(
        "Le nom et le prénom doivent être des chaînes de caractères valides entre 3 et 50 caractères."
      );
    }
    if (age !== null && age <= 13) {
      throw new Error("L'âge doit être supérieur à 13 ans.");
    }
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      throw new Error("L'email doit être valide.");
    }
    event.target.submit();
  } catch (error) {
    errorDiv.textContent = error.message;
  }
}

document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector("form");
  if (form) {
    form.addEventListener("submit", validateForm);
  }
});
