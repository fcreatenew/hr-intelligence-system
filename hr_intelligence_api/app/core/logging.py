import logging

from app.core.config import LOG_LEVEL


def setup_logging():
    logging.basicConfig(
        level=LOG_LEVEL,
        format=(
            "%(asctime)s | "
            "%(levelname)-8s | "
            "%(name)s | "
            "%(message)s"
        ),
    )