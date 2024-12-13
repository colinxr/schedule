'use client'
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useState, useEffect } from 'react';
import { ChevronRight, LayoutDashboard, Settings, FolderKanban } from "lucide-react";
import { useOpenConversations } from '@/hooks/useConversationSelection';
import { OpenConversationStore } from '@/stores/openConversationStore';

const navigation = [
  { name: 'Dashboard', href: '/a', icon: LayoutDashboard },
  { name: 'Projects', href: '/a/projects', icon: FolderKanban },
  { name: 'Settings', href: '/a/settings', icon: Settings },
];

export default function Navbar() {
  const pathname = usePathname();
  const isConversationRoute = /\/conversations\/\w+/.test(pathname || '');
  const isStoreOpen = useOpenConversations((state: OpenConversationStore) => state.isOpen);
  
  const defaultState = isConversationRoute || window.innerWidth < 768 ? false : true;
  const [isOpen, setIsOpen] = useState(defaultState);
  
  // Add effect to handle store changes
  useEffect(() => {
    if (isStoreOpen) {
      setIsOpen(false);
    }
  }, [isStoreOpen]);

  useEffect(() => {
    const handleResize = () => {
      const isConversationRoute = /\/conversations\/\w+/.test(pathname || '');
      const newState = isConversationRoute || window.innerWidth < 768 ? false : true;
      setIsOpen(newState);
    };
    
    handleResize();
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, [pathname]);

  return (
    <aside className={cn(
      "h-screen bg-background border-r transition-all duration-300 hidden md:block",
      isOpen ? "w-[250px]" : "w-[60px]"
    )}>
      <div className="h-16 flex items-center px-4 border-b justify-between">
        {isOpen && <h2 className="font-semibold">Schedule</h2>}
        <Button
          variant="ghost"
          size="icon"
          onClick={() => setIsOpen(!isOpen)}
          className={cn(
            "transition-transform",
            isOpen && "rotate-180"
          )}
        >
          <ChevronRight className="h-4 w-4" />
        </Button>
      </div>

      <nav className="p-2">
        <ul className="space-y-2">
          {navigation.map((item) => {
            const Icon = item.icon;
            return (
              <li key={item.name}>
                <Link
                  href={item.href}
                  className={cn(
                    "flex items-center gap-2 px-3 py-2 rounded-md transition-colors",
                    "hover:bg-accent hover:text-accent-foreground",
                    pathname === item.href ? "bg-accent text-accent-foreground" : "text-muted-foreground",
                    !isOpen && "justify-center"
                  )}
                >
                  <Icon className="h-4 w-4" />
                  {isOpen && <span>{item.name}</span>}
                </Link>
              </li>
            );
          })}
        </ul>
      </nav>
    </aside>
  );
} 