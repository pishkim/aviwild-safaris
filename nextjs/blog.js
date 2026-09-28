import { useEffect, useState } from 'react';

export default function BlogPage() {
  const [posts, setPosts] = useState([]);
  const [meta, setMeta] = useState(null);
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');

  useEffect(() => {
    const controller = new AbortController();

    fetch(`/api/blog?page=${page}&limit=6&status=published&search=${encodeURIComponent(search)}`, {
      signal: controller.signal,
    })
      .then(r => r.json())
      .then(json => {
        if (json.success) {
          setPosts(json.data);
          setMeta(json.pagination);
        }
      })
      .catch(err => {
        if (err.name !== 'AbortError') console.error(err);
      });

    return () => controller.abort();
  }, [page, search]);

  return (
    <div className="container py-4">
      <h1>Blog</h1>

      <input
        className="form-control mb-3"
        placeholder="Search posts..."
        value={search}
        onChange={e => { setPage(1); setSearch(e.target.value); }}
      />

      <div className="row">
        {posts.map(post => (
          <div className="col-md-4 mb-4" key={post.id}>
            <div className="card h-100">
              {post.image_url && (
                <img src={post.image_url} className="card-img-top" alt={post.title} />
              )}
              <div className="card-body">
                <h5>{post.title}</h5>
                <p className="text-muted small">
                  {post.author} · {new Date(post.publication_date).toLocaleDateString()}
                </p>
                <p>{post.introduction.slice(0, 120)}…</p>
                {post.call_to_action && (
                  <a href={`/blog/${post.id}`} className="btn btn-primary btn-sm">
                    {post.call_to_action}
                  </a>
                )}
              </div>
            </div>
          </div>
        ))}
      </div>

      {meta && meta.pages > 1 && (
        <nav>
          <ul className="pagination">
            {Array.from({ length: meta.pages }, (_, i) => i + 1).map(n => (
              <li key={n} className={`page-item ${n === page ? 'active' : ''}`}>
                <button className="page-link" onClick={() => setPage(n)}>{n}</button>
              </li>
            ))}
          </ul>
        </nav>
      )}
    </div>
  );
}

//(Optional) Direct server-side fetch in a Server Component (App Router)
// app/blog/page.js  (App Router)
// async function getPosts() {
//   const res = await fetch(`${process.env.BLOG_API_URL}/blog.php?limit=10&status=published`, {
//     headers: { 'X-API-Key': process.env.BLOG_API_KEY },
//     next: { revalidate: 60 },
//   });
//   const json = await res.json();
//   return json.data ?? [];
// }

// export default async function Page() {
//   const posts = await getPosts();
//   return (
//     <ul>
//       {posts.map(p => <li key={p.id}>{p.title}</li>)}
//     </ul>
//   );
// }