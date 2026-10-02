"""Lapisan neural network murni NumPy.

Modul ini sengaja tidak memakai PyTorch/TensorFlow agar eksperimen dapat
dijalankan di lingkungan minimal. Fokusnya adalah MLP sederhana dengan
backpropagation penuh, cukup untuk membandingkan Deep Learning dengan model
klasik (Logistic Regression, ensemble) pada kasus HR.

Semua fungsi menerima/mengembalikan `np.ndarray` dengan konvensi baris =
sampel, kolom = fitur.
"""

from __future__ import annotations

from dataclasses import dataclass, field

import numpy as np


def relu(x: np.ndarray) -> np.ndarray:
    return np.maximum(0.0, x)


def relu_grad(x: np.ndarray) -> np.ndarray:
    return (x > 0).astype(x.dtype)


def sigmoid(x: np.ndarray) -> np.ndarray:
    return 1.0 / (1.0 + np.exp(-np.clip(x, -60, 60)))


def softmax(x: np.ndarray) -> np.ndarray:
    shifted = x - x.max(axis=1, keepdims=True)
    exp = np.exp(shifted)
    return exp / exp.sum(axis=1, keepdims=True)


@dataclass
class MLP:
    """Multi-layer perceptron dengan aktivasi ReLU dan dropout opsional.

    `n_outputs=1` memakai sigmoid + binary cross-entropy (klasifikasi biner),
    lebih dari 1 memakai softmax + categorical cross-entropy (multikelas).
    """

    n_inputs: int
    hidden_sizes: tuple[int, ...] = (32, 16)
    n_outputs: int = 1
    learning_rate: float = 0.01
    dropout: float = 0.0
    seed: int = 42

    params: list[np.ndarray] = field(default_factory=list, init=False)
    biases: list[np.ndarray] = field(default_factory=list, init=False)
    history: dict = field(default_factory=dict, init=False)

    def __post_init__(self) -> None:
        rng = np.random.default_rng(self.seed)
        sizes = [self.n_inputs, *self.hidden_sizes, self.n_outputs]

        self.params = []
        self.biases = []

        for i in range(len(sizes) - 1):
            limit = np.sqrt(2.0 / sizes[i])
            self.params.append(
                rng.normal(0.0, limit, size=(sizes[i], sizes[i + 1]))
            )
            self.biases.append(np.zeros(sizes[i + 1]))

        self.history = {"loss": [], "val_loss": []}

    # ---------------------------------------------------------------- forward

    def forward(self, x: np.ndarray, training: bool = False) -> np.ndarray:
        activations = x
        self._cache = [activations]

        last = len(self.params) - 1

        for i, (w, b) in enumerate(zip(self.params, self.biases)):
            z = activations @ w + b

            if i < last:
                activations = relu(z)
                if training and self.dropout > 0:
                    mask = (
                        np.random.default_rng().random(activations.shape)
                        > self.dropout
                    )
                    activations = activations * mask / (1.0 - self.dropout)
            else:
                activations = sigmoid(z) if self.n_outputs == 1 else softmax(z)

            self._cache.append(activations)

        return activations

    def predict_proba(self, x: np.ndarray) -> np.ndarray:
        return self.forward(x, training=False)

    def predict(self, x: np.ndarray) -> np.ndarray:
        proba = self.predict_proba(x)

        if self.n_outputs == 1:
            return (proba.ravel() >= 0.5).astype(int)

        return proba.argmax(axis=1)

    # --------------------------------------------------------------- backward

    def _loss(self, y_true: np.ndarray, y_pred: np.ndarray) -> float:
        eps = 1e-12

        if self.n_outputs == 1:
            y = y_true.reshape(-1, 1)
            return float(
                -np.mean(y * np.log(y_pred + eps) + (1 - y) * np.log(1 - y_pred + eps))
            )

        idx = y_true.astype(int)
        return float(-np.mean(np.log(y_pred[np.arange(len(idx)), idx] + eps)))

    def _output_grad(
        self, y_true: np.ndarray, y_pred: np.ndarray
    ) -> np.ndarray:
        if self.n_outputs == 1:
            return (y_pred - y_true.reshape(-1, 1)) / len(y_true)

        onehot = np.zeros_like(y_pred)
        onehot[np.arange(len(y_true)), y_true.astype(int)] = 1.0
        return (y_pred - onehot) / len(y_true)

    def backward(self, x: np.ndarray, y_true: np.ndarray) -> None:
        y_pred = self._cache[-1]
        delta = self._output_grad(y_true, y_pred)

        for i in range(len(self.params) - 1, -1, -1):
            activations_in = self._cache[i]
            grad_w = activations_in.T @ delta
            grad_b = delta.sum(axis=0)

            if i > 0:
                delta = (delta @ self.params[i].T) * relu_grad(
                    self._cache[i]
                )

            self.params[i] -= self.learning_rate * grad_w
            self.biases[i] -= self.learning_rate * grad_b

    # --------------------------------------------------------------- training

    def fit(
        self,
        x: np.ndarray,
        y: np.ndarray,
        x_val: np.ndarray | None = None,
        y_val: np.ndarray | None = None,
        epochs: int = 200,
        batch_size: int = 64,
        patience: int = 20,
        verbose: bool = False,
    ) -> dict:
        """Latih dengan mini-batch SGD dan early stopping sederhana.

        Early stopping memantau val_loss bila data validasi tersedia, jika
        tidak memantau train loss. Bobot terbaik dipulihkan di akhir.
        """
        rng = np.random.default_rng(self.seed)
        n = len(x)
        best = np.inf
        best_params = None
        best_biases = None
        wait = 0

        for epoch in range(epochs):
            order = rng.permutation(n)

            for start in range(0, n, batch_size):
                idx = order[start : start + batch_size]
                self.forward(x[idx], training=True)
                self.backward(x[idx], y[idx])

            # Pantau loss pada subset train agar tidak menghitung forward pass
            # penuh setiap epoch.
            monitor_n = min(n, 512)
            sample = rng.choice(n, size=monitor_n, replace=False)
            train_pred = self.forward(x[sample], training=False)
            train_loss = self._loss(y[sample], train_pred)
            self.history["loss"].append(train_loss)

            monitor = train_loss

            if x_val is not None and y_val is not None:
                val_pred = self.forward(x_val, training=False)
                val_loss = self._loss(y_val, val_pred)
                self.history["val_loss"].append(val_loss)
                monitor = val_loss

            if monitor < best - 1e-5:
                best = monitor
                best_params = [w.copy() for w in self.params]
                best_biases = [b.copy() for b in self.biases]
                wait = 0
            else:
                wait += 1

            if verbose and epoch % 10 == 0:
                print(f"epoch {epoch:4d} loss={train_loss:.4f} monitor={monitor:.4f}")

            if wait >= patience:
                break

        if best_params is not None:
            self.params = best_params
            self.biases = best_biases

        return self.history

    # ------------------------------------------------------------ persistence

    def to_dict(self) -> dict:
        return {
            "n_inputs": self.n_inputs,
            "hidden_sizes": list(self.hidden_sizes),
            "n_outputs": self.n_outputs,
            "learning_rate": self.learning_rate,
            "dropout": self.dropout,
            "seed": self.seed,
            "params": [w.tolist() for w in self.params],
            "biases": [b.tolist() for b in self.biases],
            "history": self.history,
        }

    @classmethod
    def from_dict(cls, payload: dict) -> "MLP":
        model = cls(
            n_inputs=payload["n_inputs"],
            hidden_sizes=tuple(payload["hidden_sizes"]),
            n_outputs=payload["n_outputs"],
            learning_rate=payload.get("learning_rate", 0.01),
            dropout=payload.get("dropout", 0.0),
            seed=payload.get("seed", 42),
        )
        model.params = [np.asarray(w) for w in payload["params"]]
        model.biases = [np.asarray(b) for b in payload["biases"]]
        model.history = payload.get("history", {"loss": [], "val_loss": []})
        return model
