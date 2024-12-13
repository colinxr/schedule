'use client';

import { ReactNode } from 'react';
import Navbar from '../components/dashboard/Navbar';
import Header from '../components/dashboard/Header';
import { useOpenConversations } from '@/hooks/useConversationSelection';
import { OpenConversationStore } from '@/stores/openConversationStore';

interface DashboardLayoutProps {
  children: ReactNode;
}

export default function DashboardLayout({ children }: DashboardLayoutProps) {
  const isOpen = useOpenConversations((state: OpenConversationStore) => state.isOpen);

  return (
    <div className="min-h-screen bg-background flex w-full">
      <Navbar onConversationSelected={isOpen} />

      <div className="flex-1">
        <Header className="sticky top-0 z-50" />
        <main>
          {children}
        </main>
      </div>
    </div>
  );
} 