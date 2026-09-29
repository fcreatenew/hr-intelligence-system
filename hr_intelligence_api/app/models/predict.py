from app.core.config import MODEL_PATH
import joblib
import pandas as pd


def load_model():
    return joblib.load(MODEL_PATH)