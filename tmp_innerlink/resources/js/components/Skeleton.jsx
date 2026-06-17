export default function Skeleton({ className = '', count = 1 }) {
    const items = Array.from({ length: count });

    return (
        <>
            {items.map((_, i) => (
                <div
                    key={i}
                    className={`animate-pulse bg-primary/[0.06] rounded-md ${className}`}
                    style={count > 1 && i < count - 1 ? { marginBottom: '0.5rem' } : {}}
                />
            ))}
        </>
    );
}
