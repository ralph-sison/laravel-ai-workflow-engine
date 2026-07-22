type Variant = 'green' | 'yellow' | 'red' | 'blue' | 'gray' | 'purple';

const variants: Record<Variant, string> = {
    green:  'bg-green-100 text-green-800',
    yellow: 'bg-yellow-100 text-yellow-800',
    red:    'bg-red-100 text-red-800',
    blue:   'bg-blue-100 text-blue-800',
    gray:   'bg-gray-100 text-gray-700',
    purple: 'bg-purple-100 text-purple-800',
};

const statusVariants: Record<string, Variant> = {
    active:    'green',
    success:   'green',
    draft:     'gray',
    paused:    'yellow',
    archived:  'gray',
    running:   'blue',
    failed:    'red',
    cancelled: 'gray',
    manual:    'gray',
    webhook:   'purple',
    schedule:  'blue',
};

interface BadgeProps {
    label: string;
    variant?: Variant;
}

export default function Badge({ label, variant }: BadgeProps) {
    const v = variant ?? statusVariants[label] ?? 'gray';
    return (
        <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${variants[v]}`}>
            {label}
        </span>
    );
}
