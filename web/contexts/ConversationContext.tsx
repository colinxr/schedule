'use client';

import { createContext, useContext, useState, ReactNode } from 'react';

interface ConversationContextType {
  isSelected: boolean;
  setSelected: (selected: boolean) => void;
}

const ConversationContext = createContext<ConversationContextType | undefined>(undefined);

export function ConversationProvider({ children }: { children: ReactNode }) {
  const [isSelected, setSelected] = useState(false);

  return (
    <ConversationContext.Provider value={{ isSelected, setSelected }}>
      {children}
    </ConversationContext.Provider>
  );
}

export function useConversationSelection() {
  const context = useContext(ConversationContext);
  if (context === undefined) {
    throw new Error('useConversationSelection must be used within a ConversationProvider');
  }
  return context;
} 