# Python / LangChain Integration — Future Considerations

## Summary

For the MVP, all AI features (Phase 9) are straightforward LLM calls with structured context. PHP handles this perfectly via direct HTTP calls to the OpenAI API inside Laravel Horizon jobs. Adding a Python microservice at this stage would introduce operational complexity without real gain.

The RAG approach documented (`pgvector`) is also simple: assemble player context from the database and pass it to OpenAI. No Python needed.

---

## Where Python / LangChain Would Add Real Value (Post-MVP)

| Scenario | Why Python Wins | Relevance to Project |
|---|---|---|
| **Training video analysis** | `opencv`, `mediapipe`, pose estimation to analyze player technique from video | High — massive competitive differentiator if implemented |
| **Advanced RAG with re-ranking** | LangChain + local `sentence-transformers` (no per-embedding cost), FAISS, Cohere Rerank | Medium — when per-player data volume grows significantly |
| **Football-specific fine-tuning** | HuggingFace `transformers` ecosystem, custom datasets in Brazilian Portuguese football vocabulary | Medium — more natural responses in the context of escolinhas |
| **Time-series pattern detection** | `pandas`, `scikit-learn` to identify technical evolution trends and positional profiles | Medium — "this player has a midfielder profile, not a striker" |
| **Advanced scouting reports** | RAG over match PDFs, cross-player comparison, positional analysis | Low now, high if expanding into talent discovery |

---

## MVP Decision

Everything stays inside Laravel. The AI features are simple enough that an `Http::post()` to OpenAI inside a Horizon Job covers all documented use cases (Phase 9).

The current single-Droplet architecture also does not comfortably support a second long-running Python service.

---

## Integration Path When Needed

When there is real demand for video analysis or more advanced RAG, the natural path is:

1. Expose a **FastAPI microservice** (Python) as an internal service
2. Laravel calls it via HTTP the same way it calls any external API
3. No structural changes to the Laravel monolith are required
4. The Python service can be deployed as a separate container (Docker Compose service locally, separate Droplet or DO App Platform in production)

```
Laravel (Horizon Job)
        │
        ▼  HTTP POST /analyze
 FastAPI (Python)
        │
        ├── opencv / mediapipe   (video analysis)
        ├── sentence-transformers (embeddings)
        └── LangChain            (advanced RAG)
```

This keeps the PHP monolith as the orchestrator and the Python service as a specialized capability layer — each language doing what it does best.
