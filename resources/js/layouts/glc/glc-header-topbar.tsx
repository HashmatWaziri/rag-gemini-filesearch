import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Link } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import {
    getInitials,
    ROLE_LABELS,
    type GlcUser,
} from '@/layouts/glc/nav-config';

export function GlcHeaderTopbar({ user }: { user: GlcUser }) {
    const roleLabel = user.role ? ROLE_LABELS[user.role] : 'User';

    return (
        <div className="ms-auto flex items-center gap-3">
            <div className="hidden flex-col items-end sm:flex">
                <span className="text-sm font-medium text-mono">
                    {user.name}
                </span>
                <span className="text-xs text-muted-foreground">
                    {roleLabel}
                </span>
            </div>

            <div className="hidden h-5 w-px bg-border sm:block" />

            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        variant="outline"
                        size="icon"
                        className="size-9 rounded-full"
                        aria-label="Open account menu"
                    >
                        <Avatar className="size-8">
                            <AvatarFallback className="bg-primary/10 text-xs font-semibold text-primary">
                                {getInitials(user.name)}
                            </AvatarFallback>
                        </Avatar>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-56">
                    <DropdownMenuLabel className="font-normal">
                        <div className="flex flex-col gap-1">
                            <span className="text-sm font-medium">
                                {user.name}
                            </span>
                            <span className="text-xs text-muted-foreground">
                                {user.email}
                            </span>
                            <span className="text-xs text-muted-foreground">
                                {roleLabel}
                            </span>
                        </div>
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem asChild>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="w-full cursor-pointer"
                        >
                            <LogOut />
                            Log out
                        </Link>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    );
}
