<?php

namespace App\Services\Ai\Embeddings;

final class PgvectorAnnQuery
{
    public const INDEX_NAME = 'knowledge_embeddings_embedding_hnsw';

    public static function hnswDdl(): string
    {
        return 'CREATE INDEX IF NOT EXISTS '.self::INDEX_NAME
            .' ON knowledge_embeddings USING hnsw (embedding_vector vector_cosine_ops)'
            .' WITH (m = 16, ef_construction = 64)';
    }

    public static function searchSql(): string
    {
        return <<<'SQL'
WITH query_vec AS (
    SELECT CAST(? AS vector) AS embedding
)
SELECT
    ke.id,
    ke.source_type,
    ke.source_id,
    ke.organization_id,
    ke.embedding_model,
    ke.embedding_dimensions,
    ke.updated_at,
    (1 - (ke.embedding_vector <=> query_vec.embedding)) AS score
FROM knowledge_embeddings ke
CROSS JOIN query_vec
WHERE ke.embedding_model = ?
  AND ke.embedding_dimensions = ?
  AND ke.embedding_vector IS NOT NULL
  AND (
        (
            ke.source_type = 'library_items'
            AND ke.organization_id = ?
            AND EXISTS (
                SELECT 1
                FROM library_items li
                WHERE li.id = ke.source_id
                  AND li.organization_id = ?
                  AND li.publication_status = 'published'
            )
        )
        OR (
            ke.source_type = 'bee_knowledge_topics'
            AND ke.organization_id IS NULL
            AND EXISTS (
                SELECT 1
                FROM bee_knowledge_topics bt
                WHERE bt.id = ke.source_id
                  AND bt.is_active = true
            )
        )
  )
  AND (1 - (ke.embedding_vector <=> query_vec.embedding)) >= ?
ORDER BY ke.embedding_vector <=> query_vec.embedding ASC, ke.source_type ASC, ke.source_id ASC
LIMIT ?
SQL;
    }
}
