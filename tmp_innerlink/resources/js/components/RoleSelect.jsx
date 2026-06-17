const ROLES = [
    {
        value: 'EMPLOYEE',
        label: 'Employee',
        icon: (
            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
            </svg>
        ),
        border: 'border-l-secondary',
        dot: 'bg-secondary',
        badge: 'bg-primary/5 text-secondary',
    },
    {
        value: 'MANAGER',
        label: 'Manager',
        icon: (
            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                <path strokeLinecap="round" strokeLinejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
            </svg>
        ),
        border: 'border-l-accent',
        dot: 'bg-accent',
        badge: 'bg-accent/10 text-accent',
    },
    {
        value: 'ADMIN',
        label: 'Admin',
        icon: (
            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
            </svg>
        ),
        border: 'border-l-accent',
        dot: 'bg-accent',
        badge: 'bg-accent/10 text-accent',
    },
];

export default function RoleSelect({ value, onChange, includeAdmin = false, label }) {
    const options = ROLES.filter((r) => includeAdmin || r.value !== 'ADMIN');

    return (
        <div>
            {label && (
                <label className="block text-sm font-medium text-secondary mb-2">{label}</label>
            )}
            <div className="space-y-2">
                {options.map((opt) => {
                    const selected = opt.value === value;
                    return (
                        <button
                            key={opt.value}
                            type="button"
                            onClick={() => onChange(opt.value)}
                            className={`w-full flex items-center gap-3.5 px-4 py-3 text-left rounded-lg border transition-all ${
                                selected
                                    ? 'border-accent bg-accent/[0.03] border-l-2'
                                    : 'border-border hover:border-accent/30 hover:bg-primary/[0.02] border-l-2'
                            } ${selected ? opt.border : 'border-l-border'}`}
                        >
                            <span className={`flex items-center justify-center w-9 h-9 rounded-lg ${opt.badge}`}>
                                {opt.icon}
                            </span>
                            <div className="flex-1 min-w-0">
                                <p className={`text-sm font-semibold ${selected ? 'text-accent' : 'text-primary'}`}>
                                    {opt.label}
                                </p>
                            </div>
                            <span className={`relative flex items-center justify-center w-5 h-5 rounded-full border-2 transition-all ${
                                selected ? 'border-accent' : 'border-border'
                            }`}>
                                {selected && (
                                    <span className="absolute inset-0.5 rounded-full bg-accent" />
                                )}
                            </span>
                        </button>
                    );
                })}
            </div>
        </div>
    );
}
